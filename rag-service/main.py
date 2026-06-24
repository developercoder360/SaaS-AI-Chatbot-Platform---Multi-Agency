import os
from fastapi import FastAPI
from pydantic import BaseModel
from langchain_openai import ChatOpenAI, OpenAIEmbeddings
from langchain_community.vectorstores import PGVector

app = FastAPI()

# Make sure to set OPENAI_API_KEY in the environment
embeddings = OpenAIEmbeddings(model="text-embedding-3-small")
llm = ChatOpenAI(model="gpt-4o", temperature=0.2)

# Use environment variable or fallback to local
CONNECTION_STRING = os.getenv("DATABASE_URL", "postgresql+psycopg2://user:secret@localhost:5432/saas_chatbot")

class ChatRequest(BaseModel):
    tenant_id: str
    tenant_slug: str
    system_prompt: str
    message: str
    conversation_id: str
    chat_history: list[dict] = []

@app.post("/chat")
async def chat(req: ChatRequest):
    collection_name = f"tenant_{req.tenant_slug}_knowledge"

    vectorstore = PGVector(
        connection_string=CONNECTION_STRING,
        embedding_function=embeddings,
        collection_name=collection_name
    )
    retriever = vectorstore.as_retriever(
        search_type="similarity",
        search_kwargs={"k": 4}
    )

    docs = retriever.get_relevant_documents(req.message)
    context = "\n\n".join([d.page_content for d in docs])

    history = [(m['user'], m['assistant']) for m in req.chat_history]

    messages = [
        {"role": "system", "content": req.system_prompt},
    ]
    
    for pair in history:
        messages.append({"role": "user", "content": pair[0]})
        messages.append({"role": "assistant", "content": pair[1]})
        
    messages.append({
        "role": "user", 
        "content": f"Context:\n{context}\n\nQuestion: {req.message}"
    })

    response = llm.invoke(messages)

    return {
        "answer": response.content,
        "conversation_id": req.conversation_id,
        "tenant_id": req.tenant_id,
    }


class IngestRequest(BaseModel):
    tenant_id: str
    tenant_slug: str
    urls: list[str] = []

@app.post("/ingest")
async def ingest(req: IngestRequest):
    from langchain.text_splitter import RecursiveCharacterTextSplitter
    import requests
    from bs4 import BeautifulSoup

    collection_name = f"tenant_{req.tenant_slug}_knowledge"
    splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=50)

    all_chunks = []
    for url in req.urls:
        try:
            res = requests.get(url, timeout=10)
            soup = BeautifulSoup(res.text, 'html.parser')
            for tag in soup(['nav', 'footer', 'script', 'style']):
                tag.decompose()
            text = soup.get_text(separator='\n', strip=True)
            all_chunks.extend(splitter.split_text(text))
        except Exception as e:
            print(f"Failed {url}: {e}")

    vectorstore = PGVector(
        connection_string=CONNECTION_STRING,
        embedding_function=embeddings,
        collection_name=collection_name
    )
    
    # Delete existing collection and re-ingest fresh
    vectorstore.delete_collection()
    vectorstore.create_collection()
    
    if all_chunks:
        vectorstore.add_texts(all_chunks)

    return {"status": "done", "chunks": len(all_chunks)}

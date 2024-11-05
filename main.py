from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel
from sqlalchemy import create_engine, Column, Integer, String
from sqlalchemy.orm import sessionmaker, Session, declarative_base
from sqlalchemy.future import select

app = FastAPI()

# Configuración de la base de datos
DATABASE_URL = "sqlite:///./test.db"
engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# Modelo de usuario
class UsuarioDB(Base):
    __tablename__ = "usuarios"
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String, index=True)
    nivel = Column(Integer)  # Nivel del usuario

# Crear las tablas en la base de datos
Base.metadata.create_all(bind=engine)

# Clase de usuario para la API
class Usuario(BaseModel):
    id: int
    nombre: str
    nivel: int

# Función para obtener una sesión de base de datos
def get_db() -> Session:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# Ruta para agregar un usuario
@app.post("/usuarios/", response_model=Usuario)
def agregar_usuario(nombre: str, db: Session = Depends(get_db)):
    # Obtener todos los usuarios existentes
    usuarios = db.execute(select(UsuarioDB)).scalars().all()
    
    if not usuarios:  # Si no hay usuarios, crear el usuario raíz
        nuevo_usuario = UsuarioDB(nombre=nombre, nivel=0)
        db.add(nuevo_usuario)
        db.commit()
        db.refresh(nuevo_usuario)
        return Usuario(id=nuevo_usuario.id, nombre=nuevo_usuario.nombre, nivel=nuevo_usuario.nivel)

    # Contar cuántos usuarios hay en cada nivel
    niveles = {}
    for usuario in usuarios:
        if usuario.nivel not in niveles:
            niveles[usuario.nivel] = 0
        niveles[usuario.nivel] += 1

    # Determinar el siguiente nivel
    nivel = 0
    while True:
        max_usuarios_en_nivel = 3 ** nivel  # 3^n usuarios en el nivel n
        if niveles.get(nivel, 0) < max_usuarios_en_nivel:
            break
        nivel += 1

    # Crear el nuevo usuario
    nuevo_usuario = UsuarioDB(nombre=nombre, nivel=nivel)
    db.add(nuevo_usuario)
    db.commit()
    db.refresh(nuevo_usuario)
    return Usuario(id=nuevo_usuario.id, nombre=nuevo_usuario.nombre, nivel=nuevo_usuario.nivel)

# Ruta para obtener la lista de usuarios
@app.get("/usuarios/", response_model=list[Usuario])
def obtener_usuarios(db: Session = Depends(get_db)):
    usuarios = db.execute(select(UsuarioDB)).scalars().all()
    return [Usuario(id=usuario.id, nombre=usuario.nombre, nivel=usuario.nivel) for usuario in usuarios]

# Ruta para obtener un usuario por ID
@app.get("/usuarios/{usuario_id}", response_model=Usuario)
def obtener_usuario(usuario_id: int, db: Session = Depends(get_db)):
    usuario = db.get(UsuarioDB, usuario_id)
    if usuario is None:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")
    return Usuario(id=usuario.id, nombre=usuario.nombre, nivel=usuario.nivel)

# Ruta para eliminar un usuario por ID
@app.delete("/usuarios/{usuario_id}")
def eliminar_usuario(usuario_id: int, db: Session = Depends(get_db)):
    usuario = db.get(UsuarioDB, usuario_id)
    if usuario is None:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")
    db.delete(usuario)
    db.commit()
    return {"detail": "Usuario eliminado."}

# Iniciar el servidor
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8000)

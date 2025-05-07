"use client"

// We don't need to import the JS file directly in Next.js
// Instead, we'll create a proper React component

export default function SyntheticV0PageForDeployment() {
  return (
    <div className="h-screen w-full flex items-center justify-center">
      <div className="text-center">
        <h1 className="text-2xl font-bold mb-4">Sistema de Gestão de Produtos</h1>
        <p className="mb-4">Carregando aplicação PHP...</p>
        <p>
          <a href="/index.php" className="text-blue-500 hover:underline">
            Ir para o sistema
          </a>
        </p>
      </div>
    </div>
  )
}

import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
// import App from './App.jsx'
import Sistema_inventario from './sistema-inventario.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Sistema_inventario />
    {/* <App /> */}
  </StrictMode>,
)

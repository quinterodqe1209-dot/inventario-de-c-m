import { BrowserRouter, Routes, Route } from "react-router-dom";

import Inventario from "./pages/Inventario";
import RegistrarPQR from "./pages/RegistrarPQR";
import ConsultarPQR from "./pages/ConsultarPQR";
import PerfilUsuario from "./pages/PerfilUsuario";

function App() {

  return (
    <BrowserRouter>

      <Routes>

        <Route path="/" element={<Inventario />} />

        <Route
          path="/registrar-pqr"
          element={<RegistrarPQR />}
        />

        <Route
          path="/consultar-pqr"
          element={<ConsultarPQR />}
        />

        <Route
          path="/perfil"
          element={<PerfilUsuario />}
        />

      </Routes>

    </BrowserRouter>
  );
}

export default App;
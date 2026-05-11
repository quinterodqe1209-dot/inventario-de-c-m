import { BrowserRouter, Routes, Route } from "react-router-dom";
import StockPage from "./pages/StockPage";
import RegisterPQR from "./pages/RegisterPQR";
import ConsultPQR from "./pages/ConsultPQR";
import Profile from "./pages/Profile";

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<StockPage />} />
        <Route path="/registrar-pqr" element={<RegisterPQR />} />
        <Route path="/consultar-pqr" element={<ConsultPQR />} />
        <Route path="/perfil" element={<Profile />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
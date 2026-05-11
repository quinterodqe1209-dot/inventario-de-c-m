import { useState } from "react";

const USERS = [
  { username: "gerente", password: "1234", role: "gerente", name: "Carlos Mendoza" },
  { username: "empleado", password: "abcd", role: "empleado", name: "Ana Torres" },
];

const INITIAL_PRODUCTS = [
  { id: 1, name: "lijas 3/4", category: "Electrónica", stock: 12, price: 2500000, sold: 8 },
  { id: 2, name: "Mouse Inalámbrico", category: "Accesorios", stock: 45, price: 85000, sold: 23 },
  { id: 3, name: "Teclado Mecánico", category: "Accesorios", stock: 20, price: 320000, sold: 15 },
  { id: 4, name: "Monitor 24\"", category: "Electrónica", stock: 7, price: 890000, sold: 5 },
  { id: 5, name: "Audífonos Sony", category: "Audio", stock: 30, price: 450000, sold: 19 },
];

const fmt = (n) => new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", minimumFractionDigits: 0 }).format(n);

export default function App() {
  const [user, setUser] = useState(null);
  const [products, setProducts] = useState(INITIAL_PRODUCTS);
  const [view, setView] = useState("dashboard");

  if (!user) return <LoginScreen onLogin={setUser} />;

  return (
    <div style={{ minHeight: "100vh", background: "#F7F6F2", fontFamily: "'DM Sans', sans-serif" }}>
      <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet" />
      <Nav user={user} view={view} setView={setView} onLogout={() => { setUser(null); setView("dashboard"); }} />
      <main style={{ maxWidth: 1100, margin: "0 auto", padding: "2rem 1.5rem" }}>
        {view === "dashboard" && <Dashboard products={products} user={user} />}
        {view === "inventory" && <Inventory products={products} />}
        {view === "register" && (user.role === "gerente" || user.role === "empleado") && (
          <RegisterProduct onAdd={(p) => setProducts([...products, { ...p, id: products.length + 1, sold: 0 }])} />
        )}
      </main>
    </div>
  );
}

function Nav({ user, view, setView, onLogout }) {
  const tabs = [
    { id: "dashboard", label: "Ventas", icon: "📊", roles: ["gerente"] },
    { id: "inventory", label: "Inventario", icon: "📦", roles: ["gerente", "empleado"] },
    { id: "register", label: "Nuevo Producto", icon: "➕", roles: ["gerente", "empleado"] },
  ].filter(t => t.roles.includes(user.role));

  return (
    <header style={{ background: "#1A1A2E", borderBottom: "1px solid #2D2D4A" }}>
      <div style={{ maxWidth: 1100, margin: "0 auto", padding: "0 1.5rem", display: "flex", alignItems: "center", justifyContent: "space-between", height: 60 }}>
        <div style={{ display: "flex", alignItems: "center", gap: 32 }}>
          <span style={{ fontFamily: "'Sora', sans-serif", fontWeight: 700, fontSize: 18, color: "#E8C97B", letterSpacing: "-0.5px" }}>
            InvenSmart
          </span>
          <nav style={{ display: "flex", gap: 4 }}>
            {tabs.map(t => (
              <button key={t.id} onClick={() => setView(t.id)} style={{
                background: view === t.id ? "rgba(232,201,123,0.15)" : "transparent",
                border: "none", borderRadius: 8, padding: "6px 14px",
                color: view === t.id ? "#E8C97B" : "#8888AA",
                fontWeight: 500, fontSize: 14, cursor: "pointer",
                transition: "all 0.15s"
              }}>
                {t.icon} {t.label}
              </button>
            ))}
          </nav>
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
          <div style={{ textAlign: "right" }}>
            <p style={{ margin: 0, fontSize: 13, fontWeight: 600, color: "#E0E0F0" }}>{user.name}</p>
            <p style={{ margin: 0, fontSize: 11, color: "#8888AA", textTransform: "capitalize" }}>{user.role}</p>
          </div>
          <button onClick={onLogout} style={{
            background: "rgba(255,80,80,0.1)", border: "1px solid rgba(255,80,80,0.2)",
            borderRadius: 8, padding: "6px 12px", color: "#FF6B6B",
            fontSize: 13, cursor: "pointer", fontWeight: 500
          }}>Salir</button>
        </div>
      </div>
    </header>
  );
}

function LoginScreen({ onLogin }) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = () => {
    if (!username || !password) { setError("Por favor completa todos los campos."); return; }
    setLoading(true);
    setError("");
    setTimeout(() => {
      const found = USERS.find(u => u.username === username && u.password === password);
      if (found) { onLogin(found); }
      else { setError("Usuario o contraseña incorrectos. Acceso denegado."); }
      setLoading(false);
    }, 800);
  };

  return (
    <div style={{ minHeight: "100vh", background: "#1A1A2E", display: "flex", alignItems: "center", justifyContent: "center", fontFamily: "'DM Sans', sans-serif" }}>
      <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet" />
      <div style={{ width: "100%", maxWidth: 400, padding: "0 1.5rem" }}>
        <div style={{ textAlign: "center", marginBottom: 40 }}>
          <div style={{ display: "inline-flex", alignItems: "center", justifyContent: "center", width: 56, height: 56, borderRadius: 16, background: "rgba(232,201,123,0.15)", marginBottom: 16 }}>
            <span style={{ fontSize: 26 }}>📦</span>
          </div>
          <h1 style={{ fontFamily: "'Sora', sans-serif", color: "#E8C97B", fontSize: 28, margin: "0 0 6px", fontWeight: 700 }}>InvenSmart</h1>
          <p style={{ color: "#6666888", margin: 0, fontSize: 14 }}>Sistema de gestión de inventario</p>
        </div>
        <div style={{ background: "#232338", borderRadius: 16, padding: 32, border: "1px solid #2D2D4A" }}>
          <h2 style={{ color: "#E0E0F0", fontSize: 18, fontWeight: 600, margin: "0 0 24px" }}>Iniciar sesión</h2>
          <div style={{ marginBottom: 16 }}>
            <label style={{ display: "block", fontSize: 13, color: "#8888AA", marginBottom: 6, fontWeight: 500 }}>Usuario</label>
            <input
              value={username} onChange={e => setUsername(e.target.value)}
              onKeyDown={e => e.key === "Enter" && handleSubmit()}
              placeholder="Ingresa tu usuario"
              style={{ width: "100%", background: "#1A1A2E", border: "1px solid #3D3D5A", borderRadius: 10, padding: "10px 14px", color: "#E0E0F0", fontSize: 14, outline: "none", boxSizing: "border-box" }}
            />
          </div>
          <div style={{ marginBottom: 24 }}>
            <label style={{ display: "block", fontSize: 13, color: "#8888AA", marginBottom: 6, fontWeight: 500 }}>Contraseña</label>
            <input
              type="password" value={password} onChange={e => setPassword(e.target.value)}
              onKeyDown={e => e.key === "Enter" && handleSubmit()}
              placeholder="••••••••"
              style={{ width: "100%", background: "#1A1A2E", border: "1px solid #3D3D5A", borderRadius: 10, padding: "10px 14px", color: "#E0E0F0", fontSize: 14, outline: "none", boxSizing: "border-box" }}
            />
          </div>
          {error && (
            <div style={{ background: "rgba(255,80,80,0.1)", border: "1px solid rgba(255,80,80,0.2)", borderRadius: 8, padding: "10px 14px", marginBottom: 16 }}>
              <p style={{ color: "#FF6B6B", fontSize: 13, margin: 0 }}>⚠️ {error}</p>
            </div>
          )}
          <button onClick={handleSubmit} disabled={loading} style={{
            width: "100%", padding: "12px", borderRadius: 10, border: "none",
            background: loading ? "#3D3D5A" : "#E8C97B", color: "#1A1A2E",
            fontSize: 15, fontWeight: 600, cursor: loading ? "not-allowed" : "pointer",
            transition: "all 0.15s"
          }}>
            {loading ? "Verificando..." : "Ingresar al sistema"}
          </button>
          <div style={{ marginTop: 20, padding: "12px", background: "rgba(255,255,255,0.04)", borderRadius: 8 }}>
            <p style={{ color: "#6666888", fontSize: 12, margin: "0 0 4px" }}>Credenciales de prueba:</p>
            <p style={{ color: "#8888AA", fontSize: 12, margin: 0 }}>Gerente: <span style={{ color: "#E8C97B" }}>gerente / 1234</span></p>
            <p style={{ color: "#8888AA", fontSize: 12, margin: 0 }}>Empleado: <span style={{ color: "#E8C97B" }}>empleado / abcd</span></p>
          </div>
        </div>
      </div>
    </div>
  );
}

function Dashboard({ products, user }) {
  const totalSold = products.reduce((s, p) => s + p.sold, 0);
  const totalRevenue = products.reduce((s, p) => s + p.sold * p.price, 0);
  const topProduct = [...products].sort((a, b) => b.sold - a.sold)[0];
  const maxSold = Math.max(...products.map(p => p.sold));

  return (
    <div>
      <div style={{ marginBottom: 28 }}>
        <h2 style={{ fontFamily: "'Sora', sans-serif", fontSize: 24, fontWeight: 700, color: "#1A1A2E", margin: "0 0 4px" }}>Reporte de ventas</h2>
        <p style={{ color: "#888", margin: 0, fontSize: 14 }}>Resumen general del inventario y productos vendidos</p>
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 16, marginBottom: 28 }}>
        {[
          { label: "Unidades vendidas", value: totalSold, icon: "🛒", color: "#4F46E5" },
          { label: "Ingresos totales", value: fmt(totalRevenue), icon: "💰", color: "#059669" },
          { label: "Productos en catálogo", value: products.length, icon: "📦", color: "#D97706" },
        ].map((m, i) => (
          <div key={i} style={{ background: "#fff", borderRadius: 14, padding: "20px 24px", border: "1px solid #EBEBEB", display: "flex", alignItems: "center", gap: 16 }}>
            <div style={{ width: 48, height: 48, borderRadius: 12, background: m.color + "15", display: "flex", alignItems: "center", justifyContent: "center", fontSize: 22, flexShrink: 0 }}>{m.icon}</div>
            <div>
              <p style={{ margin: "0 0 2px", fontSize: 13, color: "#888" }}>{m.label}</p>
              <p style={{ margin: 0, fontSize: 22, fontWeight: 600, color: "#1A1A2E" }}>{m.value}</p>
            </div>
          </div>
        ))}
      </div>
      <div style={{ background: "#fff", borderRadius: 14, border: "1px solid #EBEBEB", padding: 28 }}>
        <h3 style={{ fontFamily: "'Sora', sans-serif", fontWeight: 700, fontSize: 16, color: "#1A1A2E", margin: "0 0 20px" }}>Productos vendidos</h3>
        <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
          {[...products].sort((a, b) => b.sold - a.sold).map((p, i) => (
            <div key={p.id}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 6 }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                  <span style={{ width: 22, height: 22, borderRadius: 6, background: i === 0 ? "#FEF3C7" : "#F4F4F4", color: i === 0 ? "#D97706" : "#888", fontSize: 12, fontWeight: 700, display: "inline-flex", alignItems: "center", justifyContent: "center" }}>
                    {i + 1}
                  </span>
                  <span style={{ fontSize: 14, fontWeight: 500, color: "#1A1A2E" }}>{p.name}</span>
                  <span style={{ fontSize: 11, color: "#888", background: "#F4F4F4", borderRadius: 4, padding: "2px 8px" }}>{p.category}</span>
                </div>
                <div style={{ textAlign: "right" }}>
                  <span style={{ fontSize: 14, fontWeight: 600, color: "#1A1A2E" }}>{p.sold} uds</span>
                  <span style={{ fontSize: 12, color: "#888", marginLeft: 8 }}>{fmt(p.sold * p.price)}</span>
                </div>
              </div>
              <div style={{ height: 6, background: "#F4F4F4", borderRadius: 3, overflow: "hidden" }}>
                <div style={{ height: "100%", width: `${(p.sold / maxSold) * 100}%`, background: i === 0 ? "#4F46E5" : "#A5B4FC", borderRadius: 3, transition: "width 0.6s ease" }} />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function Inventory({ products }) {
  return (
    <div>
      <div style={{ marginBottom: 28 }}>
        <h2 style={{ fontFamily: "'Sora', sans-serif", fontSize: 24, fontWeight: 700, color: "#1A1A2E", margin: "0 0 4px" }}>Inventario</h2>
        <p style={{ color: "#888", margin: 0, fontSize: 14 }}>{products.length} productos registrados en el sistema</p>
      </div>
      <div style={{ background: "#fff", borderRadius: 14, border: "1px solid #EBEBEB", overflow: "hidden" }}>
        <table style={{ width: "100%", borderCollapse: "collapse", fontSize: 14 }}>
          <thead>
            <tr style={{ background: "#FAFAFA", borderBottom: "1px solid #EBEBEB" }}>
              {["Producto", "Categoría", "Stock", "Precio unitario", "Vendidos", "Estado"].map(h => (
                <th key={h} style={{ padding: "12px 18px", textAlign: "left", fontSize: 12, fontWeight: 600, color: "#888", textTransform: "uppercase", letterSpacing: "0.5px" }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {products.map((p, i) => {
              const status = p.stock === 0 ? { label: "Agotado", bg: "#FEE2E2", color: "#DC2626" }
                : p.stock < 10 ? { label: "Bajo", bg: "#FEF3C7", color: "#D97706" }
                : { label: "Disponible", bg: "#D1FAE5", color: "#059669" };
              return (
                <tr key={p.id} style={{ borderBottom: i < products.length - 1 ? "1px solid #F4F4F4" : "none" }}>
                  <td style={{ padding: "14px 18px", fontWeight: 500, color: "#1A1A2E" }}>{p.name}</td>
                  <td style={{ padding: "14px 18px", color: "#666" }}>{p.category}</td>
                  <td style={{ padding: "14px 18px", fontWeight: 600, color: p.stock < 10 ? "#D97706" : "#1A1A2E" }}>{p.stock}</td>
                  <td style={{ padding: "14px 18px", color: "#444" }}>{fmt(p.price)}</td>
                  <td style={{ padding: "14px 18px", color: "#444" }}>{p.sold}</td>
                  <td style={{ padding: "14px 18px" }}>
                    <span style={{ background: status.bg, color: status.color, fontSize: 12, fontWeight: 600, padding: "4px 10px", borderRadius: 6 }}>{status.label}</span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

function RegisterProduct({ onAdd }) {
  const [form, setForm] = useState({ name: "", category: "", stock: "", price: "" });
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState("");

  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  const handleSubmit = () => {
    if (!form.name || !form.category || !form.stock || !form.price) {
      setError("Todos los campos son obligatorios."); return;
    }
    if (isNaN(form.stock) || Number(form.stock) < 0) { setError("El stock debe ser un número válido."); return; }
    if (isNaN(form.price) || Number(form.price) <= 0) { setError("El precio debe ser mayor a cero."); return; }
    onAdd({ name: form.name, category: form.category, stock: Number(form.stock), price: Number(form.price) });
    setForm({ name: "", category: "", stock: "", price: "" });
    setSuccess(true);
    setError("");
    setTimeout(() => setSuccess(false), 3000);
  };

  const inputStyle = { width: "100%", border: "1px solid #DDDDE0", borderRadius: 10, padding: "10px 14px", fontSize: 14, color: "#1A1A2E", outline: "none", boxSizing: "border-box", background: "#fff" };
  const labelStyle = { display: "block", fontSize: 13, fontWeight: 500, color: "#555", marginBottom: 6 };

  return (
    <div>
      <div style={{ marginBottom: 28 }}>
        <h2 style={{ fontFamily: "'Sora', sans-serif", fontSize: 24, fontWeight: 700, color: "#1A1A2E", margin: "0 0 4px" }}>Registrar producto</h2>
        <p style={{ color: "#888", margin: 0, fontSize: 14 }}>Agrega un nuevo producto al inventario del sistema</p>
      </div>
      <div style={{ maxWidth: 560, background: "#fff", borderRadius: 16, border: "1px solid #EBEBEB", padding: 32 }}>
        <div style={{ display: "grid", gap: 18 }}>
          <div>
            <label style={labelStyle}>Nombre del producto *</label>
            <input value={form.name} onChange={e => set("name", e.target.value)} placeholder="Ej: Laptop HP 15" style={inputStyle} />
          </div>
          <div>
            <label style={labelStyle}>Categoría *</label>
            <select value={form.category} onChange={e => set("category", e.target.value)} style={{ ...inputStyle, appearance: "none" }}>
              <option value="">Selecciona una categoría</option>
              {["Electrónica", "Accesorios", "Audio", "Oficina", "Hogar", "Otro"].map(c => <option key={c}>{c}</option>)}
            </select>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
            <div>
              <label style={labelStyle}>Stock inicial *</label>
              <input value={form.stock} onChange={e => set("stock", e.target.value)} placeholder="0" type="number" min="0" style={inputStyle} />
            </div>
            <div>
              <label style={labelStyle}>Precio (COP) *</label>
              <input value={form.price} onChange={e => set("price", e.target.value)} placeholder="0" type="number" min="0" style={inputStyle} />
            </div>
          </div>
        </div>
        {error && (
          <div style={{ background: "#FEE2E2", borderRadius: 8, padding: "10px 14px", marginTop: 16 }}>
            <p style={{ color: "#DC2626", fontSize: 13, margin: 0 }}>⚠️ {error}</p>
          </div>
        )}
        {success && (
          <div style={{ background: "#D1FAE5", borderRadius: 8, padding: "10px 14px", marginTop: 16 }}>
            <p style={{ color: "#059669", fontSize: 13, margin: 0 }}>✅ Producto registrado exitosamente en el inventario.</p>
          </div>
        )}
        <button onClick={handleSubmit} style={{
          marginTop: 24, width: "100%", padding: "12px", borderRadius: 10, border: "none",
          background: "#1A1A2E", color: "#E8C97B", fontSize: 15, fontWeight: 600, cursor: "pointer"
        }}>
          ➕ Registrar producto
        </button>
      </div>
    </div>
  );
}

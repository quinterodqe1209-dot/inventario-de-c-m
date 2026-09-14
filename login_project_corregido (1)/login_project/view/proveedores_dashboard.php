<!doctype html>
<html lang="es" class="dark">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>C&M Soluciones Abrasivas - Dashboard Proveedores & Stock</title>    <meta name="description" content="Sistema ERP industrial de gestión de proveedores, control de stock mínimo crítico, órdenes de compra automatizadas y cuentas por pagar." />
    <meta property="og:title" content="C&M Soluciones Abrasivas - Dashboard Proveedores & Stock" />
    <meta property="og:description" content="Sistema ERP industrial de gestión de proveedores, control de stock mínimo crítico, órdenes de compra automatizadas y cuentas por pagar." />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- React & React DOM CDN -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Babel standalone for runtime JSX compilation -->
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
              mono: ['JetBrains Mono', 'monospace'],
              chakra: ['Chakra Petch', 'sans-serif'],
            }
          }
        }
      }
    </script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
  </head>
  <body class="bg-[#0c0d0e] text-[#e2e8f0] font-sans antialiased selection:bg-[#d97706]/30 selection:text-amber-200">
    <div id="root"></div>

    <script type="text/babel">
      const { useState, useEffect, useMemo } = React;

      // Componente wrapper para Iconos de Lucide
      const Icon = ({ name, className = "h-4 w-4", ...props }) => {
        const [svgHtml, setSvgHtml] = useState('');

        useEffect(() => {
          if (window.lucide && window.lucide.icons[name]) {
            const iconSvg = window.lucide.createIcon(window.lucide.icons[name], {
              class: className,
              ...props
            });
            setSvgHtml(iconSvg.outerHTML);
          }
        }, [name, className]);

        return <span dangerouslySetInnerHTML={{ __html: svgHtml }} className="inline-flex items-center justify-center" />;
      };

      // --- MOCK DATA INICIAL ---
      const PROVEEDORES_INICIALES = [
        {
          id: 'prov-1',
          codigo: 'PRV-NORTON',
          razonSocial: 'Saint-Gobain Abrasivos Colombia S.A.S. (Norton)',
          nit: '860.005.123-4',
          contacto: 'Ing. Carlos Mario Restrepo',
          cargo: 'Gerente Comercial Industrial',
          email: 'pedidos.norton@saint-gobain.com',
          telefono: '+57 (604) 444-2390',
          ciudad: 'Medellín / Guarne, Antioquia',
          leadTimePromedioDias: 3,
          calificacionCumplimiento: 97.5,
          condicionPagoDias: 45,
          limiteCreditoCOP: 180000000,
          cuentaBancaria: {
            banco: 'Bancolombia',
            tipo: 'Corriente',
            numero: '104-589234-11',
          },
        },
        {
          id: 'prov-2',
          codigo: 'PRV-3M',
          razonSocial: '3M Colombia S.A.S. (División Abrasivos)',
          nit: '860.002.435-8',
          contacto: 'Dra. Marcela Echeverri',
          cargo: 'Key Account Manager B2B',
          email: 'contacto.abrasivos@3m.com',
          telefono: '+57 (601) 410-8555',
          ciudad: 'Bogotá D.C.',
          leadTimePromedioDias: 5,
          calificacionCumplimiento: 94.0,
          condicionPagoDias: 30,
          limiteCreditoCOP: 220000000,
          cuentaBancaria: {
            banco: 'Banco de Bogotá',
            tipo: 'Corriente',
            numero: '012-984521-45',
          },
        },
        {
          id: 'prov-3',
          codigo: 'PRV-TYROLIT',
          razonSocial: 'Tyrolit Andina S.A.S.',
          nit: '900.234.871-2',
          contacto: 'Andrés Felipe Gómez',
          cargo: 'Jefe de Operaciones y Despachos',
          email: 'ventas.colombia@tyrolit.com',
          telefono: '+57 (602) 690-1122',
          ciudad: 'Cali, Valle del Cauca',
          leadTimePromedioDias: 4,
          calificacionCumplimiento: 91.8,
          condicionPagoDias: 30,
          limiteCreditoCOP: 120000000,
          cuentaBancaria: {
            banco: 'Davivienda',
            tipo: 'Ahorros',
            numero: '458-112340-90',
          },
        },
        {
          id: 'prov-4',
          codigo: 'PRV-KLINGSPOR',
          razonSocial: 'Klingspor Schleifsysteme S.A.S.',
          nit: '901.445.890-1',
          contacto: 'Helena von Bauer',
          cargo: 'Coordinadora de Exportaciones y Distribución',
          email: 'pedidos@klingspor.com.co',
          telefono: '+57 (604) 321-7788',
          ciudad: 'Rionegro, Antioquia',
          leadTimePromedioDias: 6,
          calificacionCumplimiento: 96.2,
          condicionPagoDias: 60,
          limiteCreditoCOP: 150000000,
          cuentaBancaria: {
            banco: 'Bancolombia',
            tipo: 'Corriente',
            numero: '302-771920-56',
          },
        }
      ];

      const PRODUCTOS_INICIALES = [
        {
          id: 'prod-1',
          sku: 'ABR-DC-045',
          nombre: 'Disco de Corte Ultra Fino 4 1/2" x 1.0mm Inox BNA-12',
          descripcionTecnica: 'Disco abrasivo de corte para acero inoxidable y metales ferrosos. Diámetro 115mm, eje 22.2mm, velocidad max 13.300 RPM.',
          categoria: 'Discos de Corte',
          unidadMedida: 'Caja x 50',
          stockActual: 0,
          stockMinimo: 80,
          stockOptimo: 250,
          stockEnTransito: 0,
          costoUnitarioCOP: 125000,
          proveedorId: 'prov-1',
          ubicacionBodega: 'Bodega Principal - Rack A-02',
          consumoPromedioDiario: 12,
          diasInventarioRestante: 0,
        },
        {
          id: 'prod-2',
          sku: 'ABR-RF-060',
          nombre: 'Rueda Flap Circonio Premium 7" x 7/8" Grano 60',
          descripcionTecnica: 'Disco de láminas abrasivas con mineral de circonio de alto rendimiento para desbaste y acabado intermedio.',
          categoria: 'Lijas y Ruedas Flap',
          unidadMedida: 'Caja x 25',
          stockActual: 14,
          stockMinimo: 50,
          stockOptimo: 180,
          stockEnTransito: 0,
          costoUnitarioCOP: 215000,
          proveedorId: 'prov-1',
          ubicacionBodega: 'Bodega Principal - Rack B-05',
          consumoPromedioDiario: 7,
          diasInventarioRestante: 2,
        },
        {
          id: 'prod-3',
          sku: 'ABR-CUB-984F',
          nombre: 'Disco de Fibra Cubitron II 984F 5" x 7/8" Grano 36+',
          descripcionTecnica: 'Grano cerámico de precisión patentado 3M PSG. Para desbaste de soldadura pesado sin calentamiento de pieza.',
          categoria: 'Lijas y Ruedas Flap',
          unidadMedida: 'Caja x 25',
          stockActual: 18,
          stockMinimo: 40,
          stockOptimo: 120,
          stockEnTransito: 25,
          costoUnitarioCOP: 340000,
          proveedorId: 'prov-2',
          ubicacionBodega: 'Bodega Específica - Gaveta C-11',
          consumoPromedioDiario: 5,
          diasInventarioRestante: 3.6,
        },
        {
          id: 'prod-4',
          sku: 'ABR-BA-120C',
          nombre: 'Banda Abrasiva Carburo de Silicio 50 x 2000mm Grano 120',
          descripcionTecnica: 'Banda sin fin tela impermeable para pulido de mármol, vidrio, fundición y titanio en lijadoras de pedestal.',
          categoria: 'Bandas Abrasivas',
          unidadMedida: 'Paquete x 10',
          stockActual: 8,
          stockMinimo: 30,
          stockOptimo: 90,
          stockEnTransito: 0,
          costoUnitarioCOP: 180000,
          proveedorId: 'prov-4',
          ubicacionBodega: 'Bodega de Bandas - Gancho F-03',
          consumoPromedioDiario: 3,
          diasInventarioRestante: 2.6,
        },
        {
          id: 'prod-5',
          sku: 'ABR-DD-090',
          nombre: 'Disco de Desbaste Pesado 9" x 1/4" A24R Estructura Reforzada',
          descripcionTecnica: 'Disco abrasivo aglomerado para desbaste pesado en calderería y estructuras metálicas de gran calibre.',
          categoria: 'Discos de Desbaste',
          unidadMedida: 'Caja x 25',
          stockActual: 22,
          stockMinimo: 35,
          stockOptimo: 100,
          stockEnTransito: 0,
          costoUnitarioCOP: 295000,
          proveedorId: 'prov-3',
          ubicacionBodega: 'Bodega Principal - Rack A-08',
          consumoPromedioDiario: 4,
          diasInventarioRestante: 5.5,
        },
        {
          id: 'prod-6',
          sku: 'ABR-DIA-SEG7',
          nombre: 'Disco Diamantado Segmentado Láser 7" Concreto Curado',
          descripcionTecnica: 'Disco de corte en seco/húmedo con insertos diamantados soldadura láser para obras civiles e industria pesada.',
          categoria: 'Superabrasivos Diamantados',
          unidadMedida: 'Unidad',
          stockActual: 65,
          stockMinimo: 20,
          stockOptimo: 80,
          stockEnTransito: 0,
          costoUnitarioCOP: 145000,
          proveedorId: 'prov-3',
          ubicacionBodega: 'Bodega de Alta Seguridad - Vitrina S-01',
          consumoPromedioDiario: 1.5,
          diasInventarioRestante: 43.3,
        },
        {
          id: 'prod-7',
          sku: 'ABR-DC-070',
          nombre: 'Disco de Corte 7" x 1/16" A60S Acero Carbono / Inox',
          descripcionTecnica: 'Corte rápido libre de rebabas para perfiles y tubos estructurales.',
          categoria: 'Discos de Corte',
          unidadMedida: 'Caja x 25',
          stockActual: 95,
          stockMinimo: 40,
          stockOptimo: 130,
          stockEnTransito: 0,
          costoUnitarioCOP: 155000,
          proveedorId: 'prov-1',
          ubicacionBodega: 'Bodega Principal - Rack A-03',
          consumoPromedioDiario: 3.2,
          diasInventarioRestante: 29.6,
        },
        {
          id: 'prod-8',
          sku: 'ABR-RO-CER2',
          nombre: 'Disco Roloc Scotch-Brite Abrasivo Cerámico 2" Mediano',
          descripcionTecnica: 'Sistema de cambio rápido Roloc para desbaste fino y limpieza de cordones de soldadura TIG/MIG.',
          categoria: 'Lijas y Ruedas Flap',
          unidadMedida: 'Caja x 50',
          stockActual: 45,
          stockMinimo: 25,
          stockOptimo: 75,
          stockEnTransito: 0,
          costoUnitarioCOP: 310000,
          proveedorId: 'prov-2',
          ubicacionBodega: 'Bodega Específica - Gaveta C-08',
          consumoPromedioDiario: 2.1,
          diasInventarioRestante: 21.4,
        }
      ];

      const ORDENES_COMPRA_INICIALES = [
        {
          id: 'oc-001',
          numeroOC: 'OC-2026-0412',
          proveedorId: 'prov-2',
          proveedorNombre: '3M Colombia S.A.S. (División Abrasivos)',
          fechaEmision: '2026-09-10',
          fechaEntregaEstimada: '2026-09-15',
          estado: 'EN_TRANSITO',
          items: [
            {
              productoId: 'prod-3',
              sku: 'ABR-CUB-984F',
              descripcion: 'Disco de Fibra Cubitron II 984F 5" x 7/8" Grano 36+',
              cantidad: 25,
              precioUnitarioCOP: 340000,
              subtotalCOP: 8500000,
            }
          ],
          subtotalCOP: 8500000,
          ivaCOP: 1615000,
          totalCOP: 10115000,
          notas: 'Entrega prioritaria en muelle de recepción Planta Girardota.',
          generadaAutomaticamente: true,
          creadaPor: 'ERP Stock-Engine (Auto Trigger)',
          historialEstados: [
            { estado: 'EMITIDA', fecha: '2026-09-10 08:30', comentario: 'Generación automática por rebasar ROP.' },
            { estado: 'CONFIRMADA_PROVEEDOR', fecha: '2026-09-11 10:15', comentario: 'Aceptado por 3M B2B Portal.' },
            { estado: 'EN_TRANSITO', fecha: '2026-09-13 14:00', comentario: 'Guía Servientrega 94821038.' }
          ]
        },
        {
          id: 'oc-002',
          numeroOC: 'OC-2026-0398',
          proveedorId: 'prov-1',
          proveedorNombre: 'Saint-Gobain Abrasivos Colombia S.A.S. (Norton)',
          fechaEmision: '2026-08-28',
          fechaEntregaEstimada: '2026-09-02',
          estado: 'RECIBIDA_COMPLETA',
          items: [
            {
              productoId: 'prod-7',
              sku: 'ABR-DC-070',
              descripcion: 'Disco de Corte 7" x 1/16" A60S Acero Carbono / Inox',
              cantidad: 60,
              precioUnitarioCOP: 155000,
              subtotalCOP: 9300000,
            }
          ],
          subtotalCOP: 9300000,
          ivaCOP: 1767000,
          totalCOP: 11067000,
          notas: 'Ingresado conforme a bodega central según remisión RM-8921.',
          generadaAutomaticamente: false,
          creadaPor: 'Ing. Jonathan Quijano (Jefe de Compras)'
        }
      ];

      const FACTURAS_INICIALES = [
        {
          id: 'fac-001',
          numeroFactura: 'FE-NORTON-44120',
          ordenCompraNumero: 'OC-2026-0398',
          proveedorId: 'prov-1',
          proveedorNombre: 'Saint-Gobain Abrasivos Colombia S.A.S. (Norton)',
          fechaEmision: '2026-08-29',
          fechaVencimiento: '2026-09-08',
          subtotalCOP: 9300000,
          ivaCOP: 1767000,
          totalCOP: 11067000,
          saldoPendienteCOP: 11067000,
          estado: 'VENCIDA',
          diasMora: 6,
        },
        {
          id: 'fac-002',
          numeroFactura: 'FE-3M-902188',
          ordenCompraNumero: 'OC-2026-0355',
          proveedorId: 'prov-2',
          proveedorNombre: '3M Colombia S.A.S. (División Abrasivos)',
          fechaEmision: '2026-08-20',
          fechaVencimiento: '2026-09-19',
          subtotalCOP: 16500000,
          ivaCOP: 3135000,
          totalCOP: 19635000,
          saldoPendienteCOP: 19635000,
          estado: 'PENDIENTE',
          diasMora: 0,
        },
        {
          id: 'fac-003',
          numeroFactura: 'FE-TYROLIT-8109',
          ordenCompraNumero: 'OC-2026-0312',
          proveedorId: 'prov-3',
          proveedorNombre: 'Tyrolit Andina S.A.S.',
          fechaEmision: '2026-08-01',
          fechaVencimiento: '2026-08-31',
          subtotalCOP: 12400000,
          ivaCOP: 2356000,
          totalCOP: 14756000,
          saldoPendienteCOP: 0,
          estado: 'PAGADA',
          diasMora: 0,
          metodoPago: 'Transferencia Bancolombia - Ref #9048123',
        },
        {
          id: 'fac-004',
          numeroFactura: 'FE-KLINGSPOR-1945',
          ordenCompraNumero: 'OC-2026-0370',
          proveedorId: 'prov-4',
          proveedorNombre: 'Klingspor Schleifsysteme S.A.S.',
          fechaEmision: '2026-08-15',
          fechaVencimiento: '2026-09-25',
          subtotalCOP: 7200000,
          ivaCOP: 1368000,
          totalCOP: 8568000,
          saldoPendienteCOP: 8568000,
          estado: 'PENDIENTE',
          diasMora: 0,
        },
        {
          id: 'fac-005',
          numeroFactura: 'FE-NORTON-43900',
          ordenCompraNumero: 'OC-2026-0340',
          proveedorId: 'prov-1',
          proveedorNombre: 'Saint-Gobain Abrasivos Colombia S.A.S. (Norton)',
          fechaEmision: '2026-07-25',
          fechaVencimiento: '2026-08-25',
          subtotalCOP: 14200000,
          ivaCOP: 2698000,
          totalCOP: 16898000,
          saldoPendienteCOP: 16898000,
          estado: 'VENCIDA',
          diasMora: 20,
        }
      ];

      // --- FUNCIONES DE CÁLCULO ---
      function formatCOP(amount) {
        return new Intl.NumberFormat('es-CO', {
          style: 'currency',
          currency: 'COP',
          maximumFractionDigits: 0,
        }).format(amount);
      }

      function formatNumero(num) {
        return new Intl.NumberFormat('es-CO').format(num);
      }

      function calcularAlertasStock(productos, proveedores) {
        const mapProveedores = new Map();
        proveedores.forEach((p) => mapProveedores.set(p.id, p));
        const alertas = [];

        for (const prod of productos) {
          const proveedor = mapProveedores.get(prod.proveedorId) || {
            id: prod.proveedorId,
            codigo: 'PRV-DESCONOCIDO',
            razonSocial: 'Proveedor no asignado',
            nit: '000.000.000-0',
            contacto: 'N/A',
            cargo: 'N/A',
            email: 'sin-correo@cymabrasivos.com',
            telefono: 'N/A',
            ciudad: 'N/A',
            leadTimePromedioDias: 5,
            calificacionCumplimiento: 85,
            condicionPagoDias: 30,
            limiteCreditoCOP: 50000000,
            cuentaBancaria: { banco: 'N/A', tipo: 'Corriente', numero: 'N/A' },
          };

          if (prod.stockActual <= prod.stockMinimo) {
            const deficit = prod.stockMinimo - prod.stockActual;
            const cantidadSugerida = Math.max(prod.stockOptimo - prod.stockActual, prod.stockMinimo);
            const costoEstimado = cantidadSugerida * prod.costoUnitarioCOP;
            const porcentaje = prod.stockMinimo > 0 ? (prod.stockActual / prod.stockMinimo) * 100 : 0;
            let nivel = 'REORDEN_PREVENTIVO';
            if (prod.stockActual <= 0) {
              nivel = 'AGOTADO';
            } else if (prod.stockActual <= prod.stockMinimo * 0.5) {
              nivel = 'CRITICO';
            }
            const diasCobertura = prod.consumoPromedioDiario > 0 
              ? +(prod.stockActual / prod.consumoPromedioDiario).toFixed(1) 
              : 0;

            alertas.push({
              producto: prod,
              proveedor,
              nivel,
              deficitUnidades: deficit,
              cantidadSugeridaReorden: cantidadSugerida,
              costoEstimadoCOP: costoEstimado,
              porcentajeRespectoMinimo: Math.round(porcentaje),
              diasCobertura,
            });
          }
        }

        const prioridadNivel = {
          AGOTADO: 1,
          CRITICO: 2,
          REORDEN_PREVENTIVO: 3,
          OPTIMO: 4,
        };

        return alertas.sort((a, b) => prioridadNivel[a.nivel] - prioridadNivel[b.nivel]);
      }

      function calcularResumenCuentasPagar(facturas) {
        let totalDeuda = 0;
        let totalVencido = 0;
        let facturasPendientesCount = 0;
        let facturasVencidasCount = 0;
        let facturasPagadasCount = 0;

        for (const fac of facturas) {
          if (fac.estado === 'VENCIDA') {
            totalVencido += fac.saldoPendienteCOP;
            totalDeuda += fac.saldoPendienteCOP;
            facturasVencidasCount++;
          } else if (fac.estado === 'PENDIENTE') {
            totalDeuda += fac.saldoPendienteCOP;
            facturasPendientesCount++;
          } else if (fac.estado === 'PAGADA') {
            facturasPagadasCount++;
          }
        }

        return {
          totalDeuda,
          totalVencido,
          facturasPendientesCount,
          facturasVencidasCount,
          facturasPagadasCount,
        };
      }

      // --- COMPONENTES ---

      // Header Component
      const Header = ({
        currentRole,
        setCurrentRole,
        selectedProveedorId,
        setSelectedProveedorId,
        proveedores,
        alertasCount,
        onOpenSpecs,
        onLogout,
      }) => {
        const proveedorActual = proveedores.find((p) => p.id === selectedProveedorId);

        return (
          <header className="border-b border-[#27272a] bg-[#111215] sticky top-0 z-40">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
              <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div className="flex items-center space-x-3.5">
                  <div className="h-10 w-10 rounded-lg bg-gradient-to-br from-[#d97706] to-[#b45309] p-0.5 shadow-lg shadow-amber-950/40 flex items-center justify-center">
                    <div className="w-full h-full bg-[#0d0e11] rounded-[7px] flex items-center justify-center">
                      <Icon name="layers" className="h-5 w-5 text-[#f59e0b]" />
                    </div>
                  </div>
                  <div>
                    <div className="flex items-center space-x-2">
                      <span className="font-bold tracking-wider text-base uppercase text-white font-chakra">
                        C&M Soluciones Abrasivas
                      </span>
                      <span className="text-[10px] font-semibold bg-[#27272a] text-[#fbbf24] px-1.5 py-0.5 rounded border border-[#f59e0b]/30">
                        S.A.S.
                      </span>
                      <span className="text-[10px] font-mono bg-zinc-800/80 text-zinc-400 px-1.5 py-0.5 rounded">
                        ERP v3.4.1
                      </span>
                    </div>
                    <p className="text-xs text-zinc-400 font-medium">
                      Control de Stock, Proveedores & Cuentas por Pagar
                    </p>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                  <button
                    id="btn-open-technical-specs"
                    onClick={onOpenSpecs}
                    className="inline-flex items-center space-x-2 text-xs font-mono font-medium px-3 py-2 rounded-lg bg-[#181a1f] hover:bg-[#22252c] text-amber-300 border border-amber-500/30 transition-all hover:border-amber-400 shadow-sm"
                    title="Ver especificación técnica MySQL, arquitectura y endpoints"
                  >
                    <Icon name="terminal" className="h-4 w-4 text-[#f59e0b]" />
                    <span className="hidden sm:inline">Docs Técnicos</span>
                    <span className="sm:hidden">Specs</span>
                    <span className="bg-amber-500/20 text-amber-200 text-[10px] px-1.5 py-0.2 rounded font-semibold">
                      MySQL DDL
                    </span>
                  </button>

                  <button
                    id="btn-logout"
                    type="button"
                    onClick={onLogout}
                    className="inline-flex items-center space-x-2 text-xs font-semibold px-3 py-2 rounded-lg bg-red-950/40 hover:bg-red-700 text-red-200 hover:text-white border border-red-500/40 transition-all"
                    title="Cerrar la sesión actual"
                  >
                    <Icon name="log-out" className="h-4 w-4" />
                    <span className="hidden sm:inline">Cerrar sesión</span>
                  </button>

                  <div className="flex items-center rounded-lg bg-[#090a0c] p-1 border border-zinc-800">
                    <button
                      id="role-btn-gerente"
                      onClick={() => setCurrentRole('GERENTE')}
                      className={`inline-flex items-center space-x-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-colors ${
                        currentRole === 'GERENTE'
                          ? 'bg-[#d97706] text-black font-semibold shadow-sm'
                          : 'text-zinc-400 hover:text-white'
                      }`}
                    >
                      <Icon name="shield-check" className="h-3.5 w-3.5" />
                      <span>Gerente Compras</span>
                      {alertasCount > 0 && currentRole !== 'GERENTE' && (
                        <span className="ml-1 bg-red-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                          {alertasCount}
                        </span>
                      )}
                    </button>
                    <button
                      id="role-btn-proveedor"
                      onClick={() => setCurrentRole('PROVEEDOR')}
                      className={`inline-flex items-center space-x-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-colors ${
                        currentRole === 'PROVEEDOR'
                          ? 'bg-[#d97706] text-black font-semibold shadow-sm'
                          : 'text-zinc-400 hover:text-white'
                      }`}
                    >
                      <Icon name="building-2" className="h-3.5 w-3.5" />
                      <span>Portal Proveedor</span>
                    </button>
                  </div>

                  {currentRole === 'PROVEEDOR' && (
                    <div className="flex items-center space-x-1.5 bg-[#181a1f] border border-amber-500/40 rounded-lg px-2.5 py-1.5">
                      <span className="text-[11px] text-amber-400 font-mono font-medium">Actuando como:</span>
                      <select
                        id="select-supplier-account"
                        value={selectedProveedorId}
                        onChange={(e) => setSelectedProveedorId(e.target.value)}
                        className="bg-transparent text-xs text-white font-medium focus:outline-none cursor-pointer"
                      >
                        {proveedores.map((p) => (
                          <option key={p.id} value={p.id} className="bg-[#181a1f] text-white">
                            {p.codigo} - {p.razonSocial.split(' ')[0]} {p.razonSocial.includes('Norton') ? '(Norton)' : ''}
                          </option>
                        ))}
                      </select>
                    </div>
                  )}
                </div>
              </div>

              <div className="mt-2.5 pt-2 border-t border-zinc-800/60 flex flex-wrap items-center justify-between text-xs text-zinc-400">
                <div className="flex items-center space-x-2">
                  <span className="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span>
                    Entorno Operativo: <strong className="text-zinc-200">Planta Central & Bodegas Girardota</strong>
                  </span>
                  <span className="text-zinc-600">|</span>
                  <span className="text-zinc-300">
                    Vista activa:{' '}
                    <strong className="text-amber-400">
                      {currentRole === 'GERENTE'
                        ? 'Panel Central de Gerencia de Compras & Abastecimiento'
                        : `Extranet Proveedor B2B: ${proveedorActual?.razonSocial}`}
                    </strong>
                  </span>
                </div>
                <div className="hidden lg:flex items-center space-x-3 text-zinc-400 text-[11px] font-mono">
                  <span>Sincronización MySQL: <span className="text-emerald-400">ACTIVA</span></span>
                  <span>Trigger ROP: <span className="text-amber-400">AUTOMÁTICO</span></span>
                </div>
              </div>
            </div>
          </header>
        );
      };

      // MetricCards Component
      const MetricCards = ({
        currentRole,
        alertas,
        totalDeuda,
        totalVencido,
        facturasVencidasCount,
        facturasPendientesCount,
        ordenesActivasCount,
        proveedorActual,
        onSelectAlertFilter,
      }) => {
        const agotadosCount = alertas.filter((a) => a.nivel === 'AGOTADO').length;
        const criticosCount = alertas.filter((a) => a.nivel === 'CRITICO').length;
        const reordenCount = alertas.filter((a) => a.nivel === 'REORDEN_PREVENTIVO').length;
        const costoTotalReorden = alertas.reduce((acc, a) => acc + a.costoEstimadoCOP, 0);

        if (currentRole === 'PROVEEDOR') {
          return (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div className="bg-[#121316] border border-zinc-800/90 rounded-xl p-4 shadow-sm relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                    Órdenes de Compra C&M
                  </span>
                  <div className="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <Icon name="shopping-cart" className="h-4 w-4" />
                  </div>
                </div>
                <div className="mt-3 flex items-baseline space-x-2">
                  <span className="text-2xl font-bold font-mono text-white">
                    {ordenesActivasCount}
                  </span>
                  <span className="text-xs text-amber-400 font-medium">requieren atención</span>
                </div>
                <p className="mt-1 text-xs text-zinc-400">
                  Pedidos vigentes o en proceso de despacho
                </p>
              </div>

              <div className="bg-[#121316] border border-zinc-800/90 rounded-xl p-4 shadow-sm relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                    Por Cobrar (Pendiente)
                  </span>
                  <div className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <Icon name="dollar-sign" className="h-4 w-4" />
                  </div>
                </div>
                <div className="mt-3">
                  <span className="text-xl font-bold font-mono text-white block">
                    {formatCOP(totalDeuda)}
                  </span>
                  <span className="text-xs text-zinc-400">
                    {facturasPendientesCount} facturas en trámite contable
                  </span>
                </div>
              </div>

              <div className={`bg-[#121316] border rounded-xl p-4 shadow-sm relative overflow-hidden ${
                totalVencido > 0 ? 'border-red-500/40 bg-red-950/10' : 'border-zinc-800/90'
              }`}>
                <div className="flex items-center justify-between">
                  <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                    Facturas Vencidas
                  </span>
                  <div className="p-2 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20">
                    <Icon name="shield-alert" className="h-4 w-4" />
                  </div>
                </div>
                <div className="mt-3">
                  <span className="text-xl font-bold font-mono text-red-400 block">
                    {formatCOP(totalVencido)}
                  </span>
                  <span className="text-xs text-red-300/80">
                    {facturasVencidasCount} documentos con mora
                  </span>
                </div>
              </div>

              <div className="bg-[#121316] border border-zinc-800/90 rounded-xl p-4 shadow-sm relative overflow-hidden">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                    SLA & Lead Time Acordado
                  </span>
                  <div className="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <Icon name="clock" className="h-4 w-4" />
                  </div>
                </div>
                <div className="mt-3 flex items-baseline space-x-2">
                  <span className="text-2xl font-bold font-mono text-white">
                    {proveedorActual?.leadTimePromedioDias || 4} d
                  </span>
                  <span className="text-xs text-emerald-400 font-semibold">
                    {proveedorActual?.calificacionCumplimiento || 95}% SLA
                  </span>
                </div>
                <p className="mt-1 text-xs text-zinc-400">
                  Plazo de entrega estándar a bodegas C&M
                </p>
              </div>
            </div>
          );
        }

        return (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div 
              onClick={() => onSelectAlertFilter && onSelectAlertFilter('TODAS')}
              className={`bg-[#121316] border rounded-xl p-4 shadow-sm cursor-pointer transition-all hover:border-amber-500/60 ${
                agotadosCount > 0 ? 'border-red-600/60 bg-gradient-to-br from-red-950/20 via-[#121316] to-[#121316]' : 'border-amber-600/40'
              }`}
            >
              <div className="flex items-center justify-between">
                <span className="text-xs font-mono uppercase tracking-wider text-zinc-400 flex items-center gap-1.5">
                  <span className="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                  Stock Bajo Reorden (ROP)
                </span>
                <div className="p-2 rounded-lg bg-red-500/20 text-red-400 border border-red-500/30">
                  <Icon name="alert-octagon" className="h-4 w-4" />
                </div>
              </div>
              <div className="mt-3 flex items-baseline space-x-2">
                <span className="text-3xl font-bold font-mono text-white">
                  {alertas.length}
                </span>
                <span className="text-xs text-zinc-400">productos</span>
              </div>
              <div className="mt-2.5 flex items-center gap-2 text-xs">
                <span className="inline-flex items-center px-1.5 py-0.5 rounded bg-red-500/20 text-red-300 font-medium font-mono text-[11px] border border-red-500/30">
                  {agotadosCount} Agotados
                </span>
                <span className="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-medium font-mono text-[11px] border border-amber-500/30">
                  {criticosCount} Críticos
                </span>
                <span className="text-[11px] text-zinc-500">
                  +{reordenCount} reorden
                </span>
              </div>
            </div>

            <div className="bg-[#121316] border border-zinc-800/90 hover:border-amber-500/40 transition-colors rounded-xl p-4 shadow-sm">
              <div className="flex items-center justify-between">
                <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                  Inversión Reorden Óptimo
                </span>
                <div className="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                  <Icon name="trending-down" className="h-4 w-4" />
                </div>
              </div>
              <div className="mt-3">
                <span className="text-2xl font-bold font-mono text-amber-400 block tracking-tight">
                  {formatCOP(costoTotalReorden)}
                </span>
                <p className="mt-1 text-xs text-zinc-400">
                  Monto para recuperar nivel óptimo en {alertas.length} ítems
                </p>
              </div>
              <div className="mt-2 text-[11px] text-zinc-500 font-mono">
                Cálculo: (Stock Óptimo - Stock Actual) × Costo
              </div>
            </div>

            <div className={`bg-[#121316] border rounded-xl p-4 shadow-sm transition-colors ${
              totalVencido > 0 ? 'border-red-900/60' : 'border-zinc-800/90'
            }`}>
              <div className="flex items-center justify-between">
                <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                  Cuentas por Pagar (Proveedores)
                </span>
                <div className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  <Icon name="dollar-sign" className="h-4 w-4" />
                </div>
              </div>
              <div className="mt-3">
                <span className="text-2xl font-bold font-mono text-white block tracking-tight">
                  {formatCOP(totalDeuda)}
                </span>
                <div className="mt-1.5 flex items-center justify-between text-xs">
                  <span className="text-zinc-400">
                    {facturasPendientesCount} facturas pendientes
                  </span>
                  {totalVencido > 0 && (
                    <span className="text-red-400 font-semibold font-mono bg-red-950/50 px-1.5 py-0.5 rounded border border-red-800/50">
                      {formatCOP(totalVencido)} vencido
                    </span>
                  )}
                </div>
              </div>
            </div>

            <div className="bg-[#121316] border border-zinc-800/90 rounded-xl p-4 shadow-sm">
              <div className="flex items-center justify-between">
                <span className="text-xs font-mono uppercase tracking-wider text-zinc-400">
                  Lead Time & Órdenes en Curso
                </span>
                <div className="p-2 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                  <Icon name="truck" className="h-4 w-4" />
                </div>
              </div>
              <div className="mt-3 flex items-baseline space-x-2">
                <span className="text-2xl font-bold font-mono text-white">
                  3.8 d
                </span>
                <span className="text-xs text-emerald-400 font-semibold">
                  94.8% SLA entrega
                </span>
              </div>
              <p className="mt-1 text-xs text-zinc-400">
                {ordenesActivasCount} órdenes de compra emitidas en tránsito
              </p>
              <div className="mt-2 text-[11px] text-zinc-500 font-mono">
                4 proveedores industriales auditados
              </div>
            </div>
          </div>
        );
      };

      // StockAlertsSection Component
      const StockAlertsSection = ({
        alertas,
        onGenerarOrden,
        onAjustarStock,
      }) => {
        const [filtroNivel, setFiltroNivel] = useState('TODAS');

        const alertasFiltradas = alertas.filter((a) => {
          if (filtroNivel === 'TODAS') return true;
          return a.nivel === filtroNivel;
        });

        const countAgotados = alertas.filter((a) => a.nivel === 'AGOTADO').length;
        const countCriticos = alertas.filter((a) => a.nivel === 'CRITICO').length;
        const countReorden = alertas.filter((a) => a.nivel === 'REORDEN_PREVENTIVO').length;

        return (
          <section className="bg-[#111215] border border-zinc-800 rounded-xl overflow-hidden shadow-lg">
            <div className="p-5 border-b border-zinc-800/80 bg-gradient-to-r from-[#181a1f] via-[#141518] to-[#111215] flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <div className="flex items-center space-x-2.5">
                  <div className="p-1.5 rounded-md bg-amber-500/20 text-[#fbbf24] border border-amber-500/30">
                    <Icon name="alert-triangle" className="h-5 w-5" />
                  </div>
                  <div>
                    <h2 className="text-lg font-bold text-white tracking-wide font-chakra flex items-center gap-2">
                      Alertas de Reabastecimiento Crítico
                      <span className="text-xs font-mono font-normal bg-red-500/20 text-red-300 px-2 py-0.5 rounded border border-red-500/30">
                        {alertas.length} ítems bajo ROP
                      </span>
                    </h2>
                    <p className="text-xs text-zinc-400 mt-0.5">
                      Detección automática en tiempo real de productos por debajo del punto de reorden (Stock Mínimo).
                    </p>
                  </div>
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-1.5 bg-[#0a0b0d] p-1 rounded-lg border border-zinc-800">
                <button
                  id="filter-alert-todas"
                  onClick={() => setFiltroNivel('TODAS')}
                  className={`text-xs px-3 py-1.5 rounded-md font-medium transition-colors ${
                    filtroNivel === 'TODAS'
                      ? 'bg-zinc-700 text-white font-semibold'
                      : 'text-zinc-400 hover:text-zinc-200'
                  }`}
                >
                  Todas ({alertas.length})
                </button>
                <button
                  id="filter-alert-agotado"
                  onClick={() => setFiltroNivel('AGOTADO')}
                  className={`text-xs px-2.5 py-1.5 rounded-md font-medium flex items-center gap-1.5 transition-colors ${
                    filtroNivel === 'AGOTADO'
                      ? 'bg-red-600 text-white font-semibold'
                      : 'text-red-400 hover:bg-red-950/40'
                  }`}
                >
                  <span className="w-1.5 h-1.5 rounded-full bg-red-400 animate-ping"></span>
                  Agotados ({countAgotados})
                </button>
                <button
                  id="filter-alert-critico"
                  onClick={() => setFiltroNivel('CRITICO')}
                  className={`text-xs px-2.5 py-1.5 rounded-md font-medium transition-colors ${
                    filtroNivel === 'CRITICO'
                      ? 'bg-orange-600 text-white font-semibold'
                      : 'text-orange-400 hover:bg-orange-950/40'
                  }`}
                >
                  Críticos &lt;50% ({countCriticos})
                </button>
                <button
                  id="filter-alert-reorden"
                  onClick={() => setFiltroNivel('REORDEN_PREVENTIVO')}
                  className={`text-xs px-2.5 py-1.5 rounded-md font-medium transition-colors ${
                    filtroNivel === 'REORDEN_PREVENTIVO'
                      ? 'bg-[#d97706] text-black font-semibold'
                      : 'text-amber-400 hover:bg-amber-950/40'
                  }`}
                >
                  Reorden ({countReorden})
                </button>
              </div>
            </div>

            <div className="p-5">
              {alertasFiltradas.length === 0 ? (
                <div className="text-center py-10 border border-dashed border-zinc-800 rounded-xl bg-[#0c0d10]">
                  <Icon name="check" className="h-10 w-10 text-emerald-400 mx-auto mb-2" />
                  <p className="text-sm font-semibold text-zinc-200">No hay productos en este nivel de alerta</p>
                  <p className="text-xs text-zinc-500 mt-1">El stock en inventario se encuentra dentro de los parámetros óptimos.</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {alertasFiltradas.map((alerta) => {
                    const { producto, proveedor, nivel, deficitUnidades, cantidadSugeridaReorden, costoEstimadoCOP, diasCobertura } = alerta;
                    const isAgotado = nivel === 'AGOTADO';
                    const isCritico = nivel === 'CRITICO';

                    const cardBorder = isAgotado
                      ? 'border-red-600/70 bg-gradient-to-r from-[#1c1214] via-[#141417] to-[#121316]'
                      : isCritico
                      ? 'border-orange-500/60 bg-gradient-to-r from-[#1b1513] via-[#141417] to-[#121316]'
                      : 'border-amber-500/40 bg-gradient-to-r from-[#1a1712] via-[#141417] to-[#121316]';

                    const badgeColor = isAgotado
                      ? 'bg-red-600/20 text-red-400 border-red-600/40'
                      : isCritico
                      ? 'bg-orange-500/20 text-orange-400 border-orange-500/40'
                      : 'bg-amber-500/20 text-amber-300 border-amber-500/40';

                    const progressBarColor = isAgotado
                      ? 'bg-red-500'
                      : isCritico
                      ? 'bg-orange-500'
                      : 'bg-amber-500';

                    const pctMin = producto.stockMinimo > 0 ? Math.min(100, Math.round((producto.stockActual / producto.stockMinimo) * 100)) : 0;

                    return (
                      <div
                        key={producto.id}
                        id={`alert-card-${producto.sku}`}
                        className={`border rounded-xl p-4 sm:p-5 transition-all shadow-md relative hover:border-amber-400/80 ${cardBorder}`}
                      >
                        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                          <div className="space-y-2 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold tracking-wide border ${badgeColor}`}>
                                {isAgotado && <span className="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>}
                                {isAgotado ? 'AGOTADO CRÍTICO (0 EN STOCK)' : isCritico ? 'STOCK CRÍTICO (< 50% MÍN)' : 'PUNTO DE REORDEN ALCANZADO'}
                              </span>
                              <span className="font-mono text-xs text-amber-400 bg-amber-950/40 px-2 py-0.5 rounded border border-amber-800/40">
                                SKU: {producto.sku}
                              </span>
                              <span className="text-xs text-zinc-400 bg-zinc-800/60 px-2 py-0.5 rounded">
                                {producto.categoria}
                              </span>
                              <span className="text-xs text-zinc-500 font-mono">
                                {producto.ubicacionBodega}
                              </span>
                            </div>
                            <h3 className="text-base font-bold text-white tracking-wide">
                              {producto.nombre}
                            </h3>
                            <p className="text-xs text-zinc-400 line-clamp-1">
                              {producto.descripcionTecnica}
                            </p>
                            <div className="flex flex-wrap items-center gap-3 text-xs pt-1 text-zinc-300">
                              <span className="flex items-center gap-1.5 text-zinc-300">
                                <Icon name="building" className="h-3.5 w-3.5 text-amber-400" />
                                Proveedor asignado: <strong className="text-white">{proveedor.razonSocial}</strong>
                              </span>
                              <span className="flex items-center gap-1 text-zinc-400 font-mono">
                                <Icon name="clock" className="h-3.5 w-3.5 text-blue-400" />
                                Lead Time: <strong className="text-zinc-200">{proveedor.leadTimePromedioDias} días</strong>
                              </span>
                              <span className="text-zinc-500">|</span>
                              <span className="text-zinc-400">
                                Empaque: <span className="text-zinc-200 font-medium">{producto.unidadMedida}</span>
                              </span>
                            </div>
                          </div>

                          <div className="bg-[#0b0c0e] border border-zinc-800/80 rounded-lg p-3.5 min-w-[260px] space-y-2">
                            <div className="flex justify-between items-center text-xs font-mono">
                              <span className="text-zinc-400">Stock Actual:</span>
                              <div className="flex items-center gap-1">
                                <span className={`text-base font-bold ${isAgotado ? 'text-red-400 animate-pulse' : 'text-amber-400'}`}>
                                  {producto.stockActual}
                                </span>
                                <span className="text-[10px] text-zinc-500">/ Mín {producto.stockMinimo}</span>
                              </div>
                            </div>
                            <div className="w-full bg-zinc-800 h-2.5 rounded-full overflow-hidden relative">
                              <div
                                className={`h-full transition-all duration-500 ${progressBarColor}`}
                                style={{ width: `${pctMin}%` }}
                              />
                            </div>
                            <div className="flex justify-between text-[11px] font-mono text-zinc-400">
                              <span>Déficit: <strong className="text-red-400">-{deficitUnidades}</strong></span>
                              <span>Cobertura: <strong className="text-zinc-200">{diasCobertura} días</strong></span>
                              <span>Objetivo: <strong className="text-emerald-400">{producto.stockOptimo}</strong></span>
                            </div>
                            <div className="pt-1.5 border-t border-zinc-800/80 flex items-center justify-between text-[11px]">
                              <span className="text-zinc-500 text-[10px] font-mono">Simular consumo:</span>
                              <div className="flex items-center space-x-1">
                                <button
                                  id={`btn-minus-stock-${producto.id}`}
                                  onClick={() => onAjustarStock(producto.id, Math.max(0, producto.stockActual - 5))}
                                  className="p-1 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white"
                                  title="Descontar 5 unidades"
                                >
                                  <Icon name="minus" className="h-3 w-3" />
                                </button>
                                <span className="font-mono text-xs px-1 text-zinc-300">{producto.stockActual}</span>
                                <button
                                  id={`btn-plus-stock-${producto.id}`}
                                  onClick={() => onAjustarStock(producto.id, producto.stockActual + 5)}
                                  className="p-1 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white"
                                  title="Ingresar 5 unidades"
                                >
                                  <Icon name="plus" className="h-3 w-3" />
                                </button>
                              </div>
                            </div>
                          </div>

                          <div className="flex flex-col sm:flex-row lg:flex-col justify-center gap-2 min-w-[200px]">
                            <div className="text-left lg:text-right font-mono">
                              <span className="text-[10px] uppercase text-zinc-400 block">Reorden Sugerido</span>
                              <span className="text-sm font-bold text-emerald-400 block">
                                +{cantidadSugeridaReorden} {producto.unidadMedida.split(' ')[0]}s
                              </span>
                              <span className="text-xs text-zinc-400 block">
                                {formatCOP(costoEstimadoCOP)}
                              </span>
                            </div>
                            <button
                              id={`btn-generate-po-${producto.sku}`}
                              onClick={() => onGenerarOrden(alerta)}
                              className="w-full inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-lg bg-gradient-to-r from-[#d97706] to-[#b45309] hover:from-[#f59e0b] hover:to-[#d97706] text-black font-bold text-xs uppercase tracking-wider shadow-lg shadow-amber-950/50 transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                            >
                              <Icon name="zap" className="h-4 w-4 fill-black" />
                              <span>Generar O.C. Auto</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </section>
        );
      };

      // CreateOrderModal Component
      const CreateOrderModal = ({
        alerta,
        isOpen,
        onClose,
        onConfirmOrder,
      }) => {
        if (!isOpen || !alerta) return null;

        const { producto, proveedor, cantidadSugeridaReorden } = alerta;
        const [cantidad, setCantidad] = useState(cantidadSugeridaReorden);
        const [precioUnitario, setPrecioUnitario] = useState(producto.costoUnitarioCOP);
        const [notas, setNotas] = useState(
          `Reabastecimiento urgente por nivel crítico de stock (${producto.stockActual} vs mín ${producto.stockMinimo}). Despachar según SLA acordado.`
        );

        const subtotal = cantidad * precioUnitario;
        const iva = subtotal * 0.19;
        const total = subtotal + iva;

        const fechaHoy = new Date().toISOString().split('T')[0];
        const fechaEstimadaObj = new Date();
        fechaEstimadaObj.setDate(fechaEstimadaObj.getDate() + proveedor.leadTimePromedioDias);
        const fechaEstimada = fechaEstimadaObj.toISOString().split('T')[0];

        const handleConfirmar = () => {
          const randomNum = Math.floor(1000 + Math.random() * 9000);
          const nuevaOrden = {
            id: `oc-${Date.now()}`,
            numeroOC: `OC-2026-${randomNum}`,
            proveedorId: proveedor.id,
            proveedorNombre: proveedor.razonSocial,
            fechaEmision: fechaHoy,
            fechaEntregaEstimada: fechaEstimada,
            estado: 'EMITIDA',
            items: [
              {
                productoId: producto.id,
                sku: producto.sku,
                descripcion: producto.nombre,
                cantidad: Number(cantidad),
                precioUnitarioCOP: Number(precioUnitario),
                subtotalCOP: subtotal,
              },
            ],
            subtotalCOP: subtotal,
            ivaCOP: iva,
            totalCOP: total,
            notas: notas,
            generadaAutomaticamente: true,
            creadaPor: 'ERP C&M (Módulo Reorden Automático)',
            historialEstados: [
              {
                estado: 'EMITIDA',
                fecha: new Date().toLocaleString('es-CO'),
                comentario: `Generada automáticamente desde alerta de stock. Stock actual era ${producto.stockActual}.`,
              },
            ],
          };
          onConfirmOrder(nuevaOrden);
          onClose();
        };

        return (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div className="bg-[#131418] border border-amber-500/50 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl shadow-amber-950/30">
              <div className="bg-gradient-to-r from-[#1c1a14] via-[#16171a] to-[#121316] p-5 border-b border-zinc-800 flex items-center justify-between">
                <div className="flex items-center space-x-3">
                  <div className="p-2 rounded-lg bg-amber-500/20 text-[#f59e0b] border border-amber-500/30">
                    <Icon name="zap" className="h-5 w-5 fill-[#f59e0b]" />
                  </div>
                  <div>
                    <h3 className="text-lg font-bold text-white font-chakra">
                      Generar Orden de Compra Automática
                    </h3>
                    <p className="text-xs text-amber-300 font-mono">
                      Protocolo de Abastecimiento Industrial C&M
                    </p>
                  </div>
                </div>
                <button
                  onClick={onClose}
                  className="text-zinc-400 hover:text-white p-1 rounded-lg hover:bg-zinc-800"
                >
                  <Icon name="x" className="h-5 w-5" />
                </button>
              </div>

              <div className="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <div className="bg-[#0b0c0e] border border-zinc-800 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <span className="text-[10px] uppercase font-mono text-zinc-400 block">Proveedor Vinculado</span>
                    <span className="text-sm font-bold text-white flex items-center gap-1.5 mt-0.5">
                      <Icon name="building-2" className="h-4 w-4 text-amber-400" />
                      {proveedor.razonSocial}
                    </span>
                    <span className="text-xs text-zinc-400 font-mono">
                      NIT: {proveedor.nit} | Contacto: {proveedor.contacto} ({proveedor.telefono})
                    </span>
                  </div>
                  <div className="text-left sm:text-right font-mono">
                    <span className="text-[10px] uppercase text-zinc-400 block">Lead Time / SLA</span>
                    <span className="text-xs text-emerald-400 font-bold block">
                      {proveedor.leadTimePromedioDias} días de entrega
                    </span>
                    <span className="text-[11px] text-zinc-500 block">
                      Entrega estimada: {fechaEstimada}
                    </span>
                  </div>
                </div>

                <div className="bg-[#18191e] border border-zinc-800/80 rounded-xl p-4 space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <span className="text-xs font-mono text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-800/40">
                        {producto.sku}
                      </span>
                      <h4 className="text-sm font-bold text-white mt-1">
                        {producto.nombre}
                      </h4>
                    </div>
                    <span className="text-xs text-zinc-400 font-mono">
                      Unidad: {producto.unidadMedida}
                    </span>
                  </div>
                  <div className="grid grid-cols-3 gap-2 text-center text-xs font-mono pt-2 border-t border-zinc-800">
                    <div className="bg-[#0e0f12] p-2 rounded">
                      <span className="text-zinc-500 block text-[10px]">Stock Actual</span>
                      <span className="text-red-400 font-bold text-sm">{producto.stockActual}</span>
                    </div>
                    <div className="bg-[#0e0f12] p-2 rounded">
                      <span className="text-zinc-500 block text-[10px]">Punto Reorden</span>
                      <span className="text-amber-400 font-bold text-sm">{producto.stockMinimo}</span>
                    </div>
                    <div className="bg-[#0e0f12] p-2 rounded">
                      <span className="text-zinc-500 block text-[10px]">Stock Óptimo</span>
                      <span className="text-emerald-400 font-bold text-sm">{producto.stockOptimo}</span>
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-mono text-zinc-300 mb-1.5">
                      Cantidad a Solicitar ({producto.unidadMedida}):
                    </label>
                    <div className="relative">
                      <input
                        type="number"
                        min="1"
                        value={cantidad}
                        onChange={(e) => setCantidad(Math.max(1, Number(e.target.value)))}
                        className="w-full bg-[#0b0c0e] border border-amber-500/40 focus:border-amber-400 rounded-lg px-3 py-2 text-white font-mono text-sm focus:outline-none"
                      />
                      <span className="absolute right-3 top-2.5 text-xs text-zinc-500 font-mono">
                        Sugerido: {cantidadSugeridaReorden}
                      </span>
                    </div>
                    <span className="text-[11px] text-zinc-500 mt-1 block">
                      Calculado para recuperar el nivel de stock óptimo ({producto.stockOptimo}).
                    </span>
                  </div>
                  <div>
                    <label className="block text-xs font-mono text-zinc-300 mb-1.5">
                      Precio Unitario Pactado (COP):
                    </label>
                    <input
                      type="number"
                      value={precioUnitario}
                      onChange={(e) => setPrecioUnitario(Number(e.target.value))}
                      className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg px-3 py-2 text-white font-mono text-sm focus:outline-none"
                    />
                    <span className="text-[11px] text-zinc-500 mt-1 block">
                      Tarifa catálogo proveedor {proveedor.razonSocial.split(' ')[0]}
                    </span>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-mono text-zinc-300 mb-1.5">
                    Instrucciones y Observaciones de Despacho:
                  </label>
                  <textarea
                    rows={2}
                    value={notas}
                    onChange={(e) => setNotas(e.target.value)}
                    className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg p-2.5 text-xs text-zinc-200 focus:outline-none resize-none"
                  />
                </div>

                <div className="bg-[#090a0d] border border-zinc-800 rounded-xl p-4 font-mono text-xs space-y-1.5">
                  <div className="flex justify-between text-zinc-400">
                    <span>Subtotal ({cantidad} unidades):</span>
                    <span className="text-zinc-200">{formatCOP(subtotal)}</span>
                  </div>
                  <div className="flex justify-between text-zinc-400">
                    <span>IVA (19%):</span>
                    <span className="text-zinc-200">{formatCOP(iva)}</span>
                  </div>
                  <div className="flex justify-between text-base font-bold pt-2 border-t border-zinc-800 text-white">
                    <span className="text-amber-400">Total Orden de Compra:</span>
                    <span className="text-emerald-400">{formatCOP(total)}</span>
                  </div>
                </div>
              </div>

              <div className="bg-[#0d0e11] px-6 py-4 border-t border-zinc-800 flex items-center justify-end space-x-3">
                <button
                  onClick={onClose}
                  className="px-4 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors"
                >
                  Cancelar
                </button>
                <button
                  id="btn-confirm-po-emission"
                  onClick={handleConfirmar}
                  className="inline-flex items-center space-x-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-[#d97706] to-[#f59e0b] hover:from-[#f59e0b] hover:to-[#fbbf24] text-black font-bold text-xs uppercase tracking-wider shadow-lg shadow-amber-950/60 transition-all cursor-pointer"
                >
                  <Icon name="check-circle-2" className="h-4 w-4" />
                  <span>Confirmar y Emitir Orden</span>
                </button>
              </div>
            </div>
          </div>
        );
      };

      // AccountsPayableSection Component
      const AccountsPayableSection = ({
        facturas,
        proveedores,
        currentRole,
        selectedProveedorId,
        onPagarFactura,
      }) => {
        const [filtroEstado, setFiltroEstado] = useState('TODAS');
        const [filtroProveedor, setFiltroProveedor] = useState('TODOS');
        const [busqueda, setBusqueda] = useState('');
        const [facturaSeleccionadaPago, setFacturaSeleccionadaPago] = useState(null);
        const [metodoPago, setMetodoPago] = useState('Transferencia Bancolombia');

        const facturasVisibles = facturas.filter((f) => {
          if (currentRole === 'PROVEEDOR' && f.proveedorId !== selectedProveedorId) {
            return false;
          }
          if (currentRole === 'GERENTE' && filtroProveedor !== 'TODOS' && f.proveedorId !== filtroProveedor) {
            return false;
          }
          if (filtroEstado !== 'TODAS' && f.estado !== filtroEstado) {
            return false;
          }
          if (busqueda.trim()) {
            const q = busqueda.toLowerCase();
            return (
              f.numeroFactura.toLowerCase().includes(q) ||
              f.proveedorNombre.toLowerCase().includes(q) ||
              f.ordenCompraNumero.toLowerCase().includes(q)
            );
          }
          return true;
        });

        const totalPendiente = facturasVisibles
          .filter((f) => f.estado === 'PENDIENTE')
          .reduce((sum, f) => sum + f.saldoPendienteCOP, 0);
        const totalVencido = facturasVisibles
          .filter((f) => f.estado === 'VENCIDA')
          .reduce((sum, f) => sum + f.saldoPendienteCOP, 0);
        const totalPagado = facturasVisibles
          .filter((f) => f.estado === 'PAGADA')
          .reduce((sum, f) => sum + f.totalCOP, 0);

        const handleConfirmarPago = () => {
          if (!facturaSeleccionadaPago) return;
          onPagarFactura(facturaSeleccionadaPago.id, metodoPago);
          setFacturaSeleccionadaPago(null);
        };

        return (
          <section className="bg-[#111215] border border-zinc-800 rounded-xl overflow-hidden shadow-lg space-y-0">
            <div className="p-5 border-b border-zinc-800/80 bg-gradient-to-r from-[#181a1f] via-[#141518] to-[#111215] flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="flex items-center space-x-2.5">
                <div className="p-1.5 rounded-md bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                  <Icon name="credit-card" className="h-5 w-5" />
                </div>
                <div>
                  <h2 className="text-lg font-bold text-white tracking-wide font-chakra">
                    {currentRole === 'GERENTE'
                      ? 'Gestión de Cuentas por Pagar & Facturación'
                      : 'Mis Facturas & Cuentas por Cobrar'}
                  </h2>
                  <p className="text-xs text-zinc-400 mt-0.5">
                    Control financiero de facturas comerciales, plazos de vencimiento y saldos adeudados en materiales abrasivos.
                  </p>
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-2 text-xs font-mono">
                <div className="bg-[#090a0d] border border-amber-500/30 px-3 py-1.5 rounded-lg">
                  <span className="text-zinc-400 text-[10px] block uppercase">Por Pagar</span>
                  <span className="text-amber-400 font-bold">{formatCOP(totalPendiente)}</span>
                </div>
                <div className={`px-3 py-1.5 rounded-lg border ${
                  totalVencido > 0 
                    ? 'bg-red-950/30 border-red-500/50 text-red-400 animate-pulse' 
                    : 'bg-[#090a0d] border-zinc-800 text-zinc-400'
                }`}>
                  <span className="text-[10px] block uppercase">Vencido</span>
                  <span className="font-bold">{formatCOP(totalVencido)}</span>
                </div>
                <div className="bg-[#090a0d] border border-emerald-500/30 px-3 py-1.5 rounded-lg">
                  <span className="text-zinc-400 text-[10px] block uppercase">Pagado</span>
                  <span className="text-emerald-400 font-bold">{formatCOP(totalPagado)}</span>
                </div>
              </div>
            </div>

            {currentRole === 'GERENTE' && (
              <div className="p-4 bg-[#0a0b0e] border-b border-zinc-800/80">
                <span className="text-xs font-mono uppercase tracking-wider text-zinc-400 block mb-2.5">
                  Distribución de Saldo Pendiente por Proveedor Industrial:
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                  {proveedores.map((prov) => {
                    const deudaProv = facturas
                      .filter((f) => f.proveedorId === prov.id && f.estado !== 'PAGADA')
                      .reduce((sum, f) => sum + f.saldoPendienteCOP, 0);
                    const tieneVencidas = facturas.some(
                      (f) => f.proveedorId === prov.id && f.estado === 'VENCIDA'
                    );
                    return (
                      <div
                        key={prov.id}
                        onClick={() => setFiltroProveedor(filtroProveedor === prov.id ? 'TODOS' : prov.id)}
                        className={`p-3 rounded-lg border text-xs cursor-pointer transition-all ${
                          filtroProveedor === prov.id
                            ? 'border-amber-400 bg-amber-950/20'
                            : tieneVencidas
                            ? 'border-red-900/60 bg-red-950/10 hover:border-red-500/50'
                            : 'border-zinc-800/90 bg-[#121316] hover:border-zinc-700'
                        }`}
                      >
                        <div className="flex justify-between items-start">
                          <strong className="text-white truncate block max-w-[170px]" title={prov.razonSocial}>
                            {prov.codigo}
                          </strong>
                          {tieneVencidas && (
                            <span className="text-[10px] font-mono text-red-400 bg-red-500/20 px-1 rounded">
                              Mora
                            </span>
                          )}
                        </div>
                        <span className="text-[11px] text-zinc-400 block truncate mt-0.5">
                          {prov.razonSocial}
                        </span>
                        <div className="mt-2 flex justify-between items-baseline font-mono">
                          <span className="text-zinc-500 text-[10px]">Saldo:</span>
                          <span className={`font-bold ${tieneVencidas ? 'text-red-400' : 'text-amber-300'}`}>
                            {formatCOP(deudaProv)}
                          </span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            <div className="p-4 border-b border-zinc-800 bg-[#0d0e11] flex flex-col sm:flex-row items-center justify-between gap-3">
              <div className="flex items-center space-x-1.5 bg-[#08090a] p-1 rounded-lg border border-zinc-800 w-full sm:w-auto">
                <button
                  onClick={() => setFiltroEstado('TODAS')}
                  className={`text-xs px-3 py-1.5 rounded-md font-medium transition-colors ${
                    filtroEstado === 'TODAS'
                      ? 'bg-zinc-700 text-white font-semibold'
                      : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  Todas ({facturas.length})
                </button>
                <button
                  onClick={() => setFiltroEstado('PENDIENTE')}
                  className={`text-xs px-3 py-1.5 rounded-md font-medium transition-colors ${
                    filtroEstado === 'PENDIENTE'
                      ? 'bg-[#d97706] text-black font-semibold'
                      : 'text-amber-400 hover:bg-amber-950/40'
                  }`}
                >
                  Pendientes
                </button>
                <button
                  onClick={() => setFiltroEstado('VENCIDA')}
                  className={`text-xs px-3 py-1.5 rounded-md font-medium transition-colors ${
                    filtroEstado === 'VENCIDA'
                      ? 'bg-red-600 text-white font-semibold'
                      : 'text-red-400 hover:bg-red-950/40'
                  }`}
                >
                  Vencidas
                </button>
                <button
                  onClick={() => setFiltroEstado('PAGADA')}
                  className={`text-xs px-3 py-1.5 rounded-md font-medium transition-colors ${
                    filtroEstado === 'PAGADA'
                      ? 'bg-emerald-600 text-white font-semibold'
                      : 'text-emerald-400 hover:bg-emerald-950/40'
                  }`}
                >
                  Pagadas
                </button>
              </div>

              <div className="relative w-full sm:w-64">
                <Icon name="search" className="h-4 w-4 absolute left-3 top-2.5 text-zinc-500" />
                <input
                  type="text"
                  placeholder="Buscar factura o proveedor..."
                  value={busqueda}
                  onChange={(e) => setBusqueda(e.target.value)}
                  className="w-full bg-[#08090a] border border-zinc-800 focus:border-amber-400 rounded-lg pl-9 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none"
                />
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-[#090a0c] text-zinc-400 font-mono uppercase text-[11px] border-b border-zinc-800">
                  <tr>
                    <th className="py-3 px-4">Factura Nº</th>
                    <th className="py-3 px-4">Proveedor / Razón Social</th>
                    <th className="py-3 px-4">O.C. Asociada</th>
                    <th className="py-3 px-4">Emisión</th>
                    <th className="py-3 px-4">Vencimiento</th>
                    <th className="py-3 px-4 text-right">Monto Total</th>
                    <th className="py-3 px-4 text-center">Estado</th>
                    <th className="py-3 px-4 text-right">Acción</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-800/60 font-mono">
                  {facturasVisibles.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="text-center py-8 text-zinc-500">
                        No se encontraron facturas bajo los criterios seleccionados.
                      </td>
                    </tr>
                  ) : (
                    facturasVisibles.map((factura) => {
                      const isVencida = factura.estado === 'VENCIDA';
                      const isPendiente = factura.estado === 'PENDIENTE';
                      const isPagada = factura.estado === 'PAGADA';

                      return (
                        <tr
                          key={factura.id}
                          className={`hover:bg-[#15171c] transition-colors ${
                            isVencida ? 'bg-red-950/10' : ''
                          }`}
                        >
                          <td className="py-3.5 px-4 font-bold text-white flex items-center gap-2">
                            <Icon name="file-check-2" className="h-3.5 w-3.5 text-amber-400" />
                            <span>{factura.numeroFactura}</span>
                          </td>
                          <td className="py-3.5 px-4 font-sans text-zinc-300">
                            <strong className="block text-white text-xs">{factura.proveedorNombre}</strong>
                          </td>
                          <td className="py-3.5 px-4 text-amber-400 font-mono">
                            {factura.ordenCompraNumero}
                          </td>
                          <td className="py-3.5 px-4 text-zinc-400">
                            {factura.fechaEmision}
                          </td>
                          <td className="py-3.5 px-4">
                            <span className={isVencida ? 'text-red-400 font-bold' : 'text-zinc-300'}>
                              {factura.fechaVencimiento}
                            </span>
                            {isVencida && (
                              <span className="block text-[10px] text-red-500 font-sans">
                                (Vencida hace {factura.diasMora} d)
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-4 text-right font-bold text-white">
                            {formatCOP(factura.totalCOP)}
                            {factura.saldoPendienteCOP > 0 && factura.saldoPendienteCOP !== factura.totalCOP && (
                              <span className="block text-[10px] text-amber-400">
                                Saldo: {formatCOP(factura.saldoPendienteCOP)}
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-4 text-center">
                            {isVencida && (
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-500/20 text-red-400 border border-red-500/40 animate-pulse">
                                <Icon name="alert-octagon" className="h-3 w-3" />
                                VENCIDA
                              </span>
                            )}
                            {isPendiente && (
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                <Icon name="clock" className="h-3 w-3" />
                                PENDIENTE
                              </span>
                            )}
                            {isPagada && (
                              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                <Icon name="check-circle" className="h-3 w-3" />
                                PAGADA
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-4 text-right">
                            {currentRole === 'GERENTE' && factura.estado !== 'PAGADA' ? (
                              <button
                                id={`btn-pay-${factura.id}`}
                                onClick={() => setFacturaSeleccionadaPago(factura)}
                                className="inline-flex items-center space-x-1 px-2.5 py-1 rounded bg-[#d97706]/90 hover:bg-[#d97706] text-black font-bold text-[11px] transition-colors cursor-pointer"
                              >
                                <Icon name="credit-card" className="h-3 w-3" />
                                <span>Pagar</span>
                              </button>
                            ) : (
                              <span className="text-[11px] text-zinc-500">
                                {isPagada ? 'Conciliada' : 'En trámite'}
                              </span>
                            )}
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {facturaSeleccionadaPago && (
              <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
                <div className="bg-[#131418] border border-amber-500/50 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
                  <div className="bg-gradient-to-r from-[#1b1912] to-[#121316] p-4 border-b border-zinc-800 flex justify-between items-center">
                    <div className="flex items-center space-x-2">
                      <Icon name="credit-card" className="h-5 w-5 text-amber-400" />
                      <h3 className="text-base font-bold text-white font-chakra">
                        Programar / Registrar Pago
                      </h3>
                    </div>
                    <button
                      onClick={() => setFacturaSeleccionadaPago(null)}
                      className="text-zinc-400 hover:text-white"
                    >
                      <Icon name="x" className="h-5 w-5" />
                    </button>
                  </div>
                  <div className="p-5 space-y-4 text-xs font-mono">
                    <div className="bg-[#0b0c0e] p-3 rounded-lg border border-zinc-800">
                      <div className="flex justify-between text-zinc-400">
                        <span>Factura Nº:</span>
                        <strong className="text-white">{facturaSeleccionadaPago.numeroFactura}</strong>
                      </div>
                      <div className="flex justify-between text-zinc-400 mt-1">
                        <span>Proveedor:</span>
                        <span className="text-white text-right max-w-[200px] truncate">{facturaSeleccionadaPago.proveedorNombre}</span>
                      </div>
                      <div className="flex justify-between text-zinc-400 mt-1">
                        <span>Total a Liquidar:</span>
                        <strong className="text-emerald-400 text-sm">{formatCOP(facturaSeleccionadaPago.saldoPendienteCOP)}</strong>
                      </div>
                    </div>
                    <div>
                      <label className="block text-zinc-300 mb-1">Método de Transferencia Corporativa:</label>
                      <select
                        value={metodoPago}
                        onChange={(e) => setMetodoPago(e.target.value)}
                        className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg p-2 text-white text-xs"
                      >
                        <option value="Transferencia Bancolombia C&M (Cta Cte #104-209)">Transferencia Bancolombia C&M (Cta Cte #104-209)</option>
                        <option value="Transferencia Banco de Bogotá (Cta Cte #012-984)">Transferencia Banco de Bogotá (Cta Cte #012-984)</option>
                        <option value="Cheque de Gerencia">Cheque de Gerencia</option>
                        <option value="Pago Electrónico ACH PSE">Pago Electrónico ACH PSE</option>
                      </select>
                    </div>
                    <div className="text-[11px] text-zinc-400 bg-amber-950/20 border border-amber-800/30 p-2.5 rounded">
                      Al confirmar el pago, se actualizará el estado a <strong>PAGADA</strong>, liberando el cupo de crédito con el fabricante y actualizando el libro mayor de Cuentas por Pagar.
                    </div>
                  </div>
                  <div className="bg-[#0c0d10] p-4 border-t border-zinc-800 flex justify-end space-x-2">
                    <button
                      onClick={() => setFacturaSeleccionadaPago(null)}
                      className="px-3 py-1.5 rounded text-zinc-400 hover:text-white"
                    >
                      Cancelar
                    </button>
                    <button
                      id="btn-confirm-payment-submission"
                      onClick={handleConfirmarPago}
                      className="px-4 py-1.5 rounded bg-emerald-500 hover:bg-emerald-400 text-black font-bold text-xs cursor-pointer"
                    >
                      Confirmar Pago Exitoso
                    </button>
                  </div>
                </div>
              </div>
            )}
          </section>
        );
      };

      // InventoryManager Component
      const InventoryManager = ({
        productos,
        proveedores,
        currentRole,
        selectedProveedorId,
        onUpdateProducto,
        onAjustarStock,
      }) => {
        const [busqueda, setBusqueda] = useState('');
        const [categoriaFiltro, setCategoriaFiltro] = useState('TODAS');
        const [productoEnEdicion, setProductoEnEdicion] = useState(null);
        const [editStockMinimo, setEditStockMinimo] = useState(0);
        const [editStockOptimo, setEditStockOptimo] = useState(0);
        const [editProveedorId, setEditProveedorId] = useState('');

        const mapProveedores = new Map();
        proveedores.forEach((p) => mapProveedores.set(p.id, p));

        const productosFiltrados = productos.filter((p) => {
          if (currentRole === 'PROVEEDOR' && p.proveedorId !== selectedProveedorId) {
            return false;
          }
          if (categoriaFiltro !== 'TODAS' && p.categoria !== categoriaFiltro) {
            return false;
          }
          if (busqueda.trim()) {
            const q = busqueda.toLowerCase();
            return (
              p.nombre.toLowerCase().includes(q) ||
              p.sku.toLowerCase().includes(q) ||
              p.descripcionTecnica.toLowerCase().includes(q)
            );
          }
          return true;
        });

        const handleStartEdit = (p) => {
          setProductoEnEdicion(p);
          setEditStockMinimo(p.stockMinimo);
          setEditStockOptimo(p.stockOptimo);
          setEditProveedorId(p.proveedorId);
        };

        const handleSaveEdit = () => {
          if (!productoEnEdicion) return;
          const actualizado = {
            ...productoEnEdicion,
            stockMinimo: Number(editStockMinimo),
            stockOptimo: Number(editStockOptimo),
            proveedorId: editProveedorId,
          };
          onUpdateProducto(actualizado);
          setProductoEnEdicion(null);
        };

        return (
          <section className="bg-[#111215] border border-zinc-800 rounded-xl overflow-hidden shadow-lg">
            <div className="p-5 border-b border-zinc-800/80 bg-gradient-to-r from-[#181a1f] via-[#141518] to-[#111215] flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="flex items-center space-x-2.5">
                <div className="p-1.5 rounded-md bg-amber-500/20 text-[#f59e0b] border border-amber-500/30">
                  <Icon name="package" className="h-5 w-5" />
                </div>
                <div>
                  <h2 className="text-lg font-bold text-white tracking-wide font-chakra">
                    {currentRole === 'GERENTE'
                      ? 'Catálogo de Inventario & Puntos de Reorden (ROP)'
                      : 'Mis Productos Suministrados a C&M Soluciones Abrasivas'}
                  </h2>
                  <p className="text-xs text-zinc-400 mt-0.5">
                    Conexión directa entre cada referencia abrasiva, su stock mínimo de seguridad y el fabricante asignado.
                  </p>
                </div>
              </div>

              <div className="relative w-full md:w-72">
                <Icon name="search" className="h-4 w-4 absolute left-3 top-2.5 text-zinc-500" />
                <input
                  type="text"
                  placeholder="Buscar por SKU, material o grano..."
                  value={busqueda}
                  onChange={(e) => setBusqueda(e.target.value)}
                  className="w-full bg-[#090a0d] border border-zinc-800 focus:border-amber-400 rounded-lg pl-9 pr-3 py-1.5 text-xs text-white placeholder-zinc-500 focus:outline-none"
                />
              </div>
            </div>

            <div className="p-3 bg-[#0a0b0d] border-b border-zinc-800/80 flex flex-wrap gap-1.5 overflow-x-auto text-xs">
              {['TODAS', 'Discos de Corte', 'Discos de Desbaste', 'Lijas y Ruedas Flap', 'Bandas Abrasivas', 'Superabrasivos Diamantados'].map((cat) => (
                <button
                  key={cat}
                  onClick={() => setCategoriaFiltro(cat)}
                  className={`px-3 py-1.5 rounded-md font-medium whitespace-nowrap transition-colors ${
                    categoriaFiltro === cat
                      ? 'bg-zinc-700 text-white font-semibold'
                      : 'text-zinc-400 hover:text-white hover:bg-zinc-800/50'
                  }`}
                >
                  {cat}
                </button>
              ))}
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs font-sans">
                <thead className="bg-[#090a0c] text-zinc-400 font-mono uppercase text-[11px] border-b border-zinc-800">
                  <tr>
                    <th className="py-3 px-4">SKU / Referencia</th>
                    <th className="py-3 px-4">Descripción del Material</th>
                    <th className="py-3 px-4">Fabricante / Proveedor</th>
                    <th className="py-3 px-4 text-center">Stock Actual</th>
                    <th className="py-3 px-4 text-center">Stock Mínimo (ROP)</th>
                    <th className="py-3 px-4 text-center">Stock Óptimo</th>
                    <th className="py-3 px-4 text-right">Costo Unitario</th>
                    <th className="py-3 px-4 text-center">Estado</th>
                    {currentRole === 'GERENTE' && <th className="py-3 px-4 text-right">Config</th>}
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-800/60 font-mono">
                  {productosFiltrados.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="text-center py-8 text-zinc-500">
                        No se encontraron productos registrados.
                      </td>
                    </tr>
                  ) : (
                    productosFiltrados.map((prod) => {
                      const prov = mapProveedores.get(prod.proveedorId);
                      const isAgotado = prod.stockActual <= 0;
                      const isBajoMinimo = prod.stockActual <= prod.stockMinimo;

                      return (
                        <tr key={prod.id} className="hover:bg-[#15171c] transition-colors">
                          <td className="py-3 px-4 font-bold text-amber-400">
                            {prod.sku}
                          </td>
                          <td className="py-3 px-4 font-sans text-zinc-200">
                            <strong className="block text-white text-xs">{prod.nombre}</strong>
                            <span className="text-[11px] text-zinc-400 block truncate max-w-xs">
                              {prod.descripcionTecnica}
                            </span>
                          </td>
                          <td className="py-3 px-4 font-sans text-zinc-300">
                            <span className="text-xs">{prov?.razonSocial || 'No asignado'}</span>
                            <span className="block text-[10px] text-zinc-500 font-mono">
                              Lead Time: {prov?.leadTimePromedioDias || 5} d
                            </span>
                          </td>
                          <td className="py-3 px-4 text-center">
                            <div className="flex items-center justify-center space-x-1.5">
                              <button
                                onClick={() => onAjustarStock(prod.id, Math.max(0, prod.stockActual - 1))}
                                className="p-0.5 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white"
                                title="Restar 1"
                              >
                                <Icon name="minus" className="h-3 w-3" />
                              </button>
                              <span className={`font-bold text-sm px-1 ${
                                isAgotado ? 'text-red-400 font-bold animate-pulse' : isBajoMinimo ? 'text-amber-400' : 'text-emerald-400'
                              }`}>
                                {prod.stockActual}
                              </span>
                              <button
                                onClick={() => onAjustarStock(prod.id, prod.stockActual + 1)}
                                className="p-0.5 rounded bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-white"
                                title="Sumar 1"
                              >
                                <Icon name="plus" className="h-3 w-3" />
                              </button>
                            </div>
                            <span className="text-[10px] text-zinc-500 block mt-0.5">{prod.unidadMedida}</span>
                          </td>
                          <td className="py-3 px-4 text-center text-zinc-300">
                            {prod.stockMinimo}
                          </td>
                          <td className="py-3 px-4 text-center text-emerald-400">
                            {prod.stockOptimo}
                          </td>
                          <td className="py-3 px-4 text-right text-zinc-200">
                            {formatCOP(prod.costoUnitarioCOP)}
                          </td>
                          <td className="py-3 px-4 text-center">
                            {isAgotado ? (
                              <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                                AGOTADO
                              </span>
                            ) : isBajoMinimo ? (
                              <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                REORDEN
                              </span>
                            ) : (
                              <span className="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                ÓPTIMO
                              </span>
                            )}
                          </td>
                          {currentRole === 'GERENTE' && (
                            <td className="py-3 px-4 text-right">
                              <button
                                onClick={() => handleStartEdit(prod)}
                                className="p-1.5 rounded hover:bg-zinc-800 text-zinc-400 hover:text-amber-400 transition-colors"
                                title="Editar parámetros ROP"
                              >
                                <Icon name="edit-3" className="h-4 w-4" />
                              </button>
                            </td>
                          )}
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {productoEnEdicion && (
              <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
                <div className="bg-[#131418] border border-amber-500/50 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
                  <div className="bg-gradient-to-r from-[#1b1912] to-[#121316] p-4 border-b border-zinc-800 flex justify-between items-center">
                    <h3 className="text-base font-bold text-white font-chakra">
                      Ajustar Parámetros ROP de Inventario
                    </h3>
                    <button onClick={() => setProductoEnEdicion(null)} className="text-zinc-400 hover:text-white">
                      <Icon name="x" className="h-5 w-5" />
                    </button>
                  </div>
                  <div className="p-5 space-y-4 text-xs font-mono">
                    <div>
                      <span className="text-amber-400 text-xs block">{productoEnEdicion.sku}</span>
                      <strong className="text-white text-sm block">{productoEnEdicion.nombre}</strong>
                    </div>

                    <div>
                      <label className="block text-zinc-300 mb-1">Proveedor Fabricante Asignado:</label>
                      <select
                        value={editProveedorId}
                        onChange={(e) => setEditProveedorId(e.target.value)}
                        className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg p-2 text-white text-xs"
                      >
                        {proveedores.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.codigo} - {p.razonSocial}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-zinc-300 mb-1">Stock Mínimo (Punto Reorden):</label>
                        <input
                          type="number"
                          value={editStockMinimo}
                          onChange={(e) => setEditStockMinimo(Number(e.target.value))}
                          className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg p-2 text-white font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-zinc-300 mb-1">Stock Óptimo Objetivo:</label>
                        <input
                          type="number"
                          value={editStockOptimo}
                          onChange={(e) => setEditStockOptimo(Number(e.target.value))}
                          className="w-full bg-[#0b0c0e] border border-zinc-800 focus:border-amber-400 rounded-lg p-2 text-white font-mono"
                        />
                      </div>
                    </div>
                  </div>
                  <div className="bg-[#0c0d10] p-4 border-t border-zinc-800 flex justify-end space-x-2">
                    <button
                      onClick={() => setProductoEnEdicion(null)}
                      className="px-3 py-1.5 rounded text-zinc-400 hover:text-white"
                    >
                      Cancelar
                    </button>
                    <button
                      onClick={handleSaveEdit}
                      className="px-4 py-1.5 rounded bg-amber-500 hover:bg-amber-400 text-black font-bold text-xs"
                    >
                      Guardar Cambios
                    </button>
                  </div>
                </div>
              </div>
            )}
          </section>
        );
      };

      // TechnicalSpecsModal Component
      const TechnicalSpecsModal = ({ isOpen, onClose }) => {
        const [activeTab, setActiveTab] = useState('SCHEMA');

        if (!isOpen) return null;

        const ddlSQL = `-- SCHEMA BASE DE DATOS ERP C&M SOLUCIONES ABRASIVAS
CREATE DATABASE IF NOT EXISTS \`cym_abrasivos_erp\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE \`cym_abrasivos_erp\`;

CREATE TABLE \`proveedores\` (
  \`id\` VARCHAR(36) NOT NULL PRIMARY KEY,
  \`codigo\` VARCHAR(20) NOT NULL UNIQUE,
  \`razon_social\` VARCHAR(150) NOT NULL,
  \`nit\` VARCHAR(20) NOT NULL UNIQUE,
  \`contacto_nombre\` VARCHAR(100),
  \`email_pedidos\` VARCHAR(100) NOT NULL,
  \`telefono\` VARCHAR(30),
  \`lead_time_dias\` INT NOT NULL DEFAULT 5,
  \`calificacion_cumplimiento\` DECIMAL(5,2) DEFAULT 95.00,
  \`condicion_pago_dias\` INT DEFAULT 30,
  \`limite_credito_cop\` DECIMAL(15,2) DEFAULT 100000000.00,
  \`banco_nombre\` VARCHAR(50),
  \`banco_tipo_cuenta\` ENUM('Ahorros', 'Corriente'),
  \`banco_numero_cuenta\` VARCHAR(30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE \`productos_abrasivos\` (
  \`id\` VARCHAR(36) NOT NULL PRIMARY KEY,
  \`sku\` VARCHAR(30) NOT NULL UNIQUE,
  \`nombre\` VARCHAR(150) NOT NULL,
  \`categoria\` VARCHAR(50) NOT NULL,
  \`unidad_medida\` VARCHAR(30) NOT NULL,
  \`stock_actual\` INT NOT NULL DEFAULT 0,
  \`stock_minimo\` INT NOT NULL DEFAULT 10,
  \`stock_optimo\` INT NOT NULL DEFAULT 50,
  \`costo_unitario_cop\` DECIMAL(12,2) NOT NULL,
  \`proveedor_id\` VARCHAR(36) NOT NULL,
  \`ubicacion_bodega\` VARCHAR(50),
  FOREIGN KEY (\`proveedor_id\`) REFERENCES \`proveedores\`(\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TRIGGER DE REORDEN AUTOMATICO
DELIMITER //
CREATE TRIGGER \`check_rop_trigger\` AFTER UPDATE ON \`productos_abrasivos\`
FOR EACH ROW
BEGIN
  IF NEW.stock_actual <= NEW.stock_minimo THEN
    INSERT INTO \`alertas_log\` (\`producto_id\`, \`stock_evento\`, \`fecha\`)
    VALUES (NEW.id, NEW.stock_actual, NOW());
  END IF;
END; //
DELIMITER ;`;

        return (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-fadeIn">
            <div className="bg-[#111215] border border-amber-500/40 rounded-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden shadow-2xl">
              <div className="p-4 bg-[#181a1f] border-b border-zinc-800 flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <Icon name="terminal" className="h-5 w-5 text-amber-400" />
                  <h3 className="text-base font-bold text-white font-chakra">
                    Especificación Técnica ERP C&M - Backend & DB Architecture
                  </h3>
                </div>
                <button onClick={onClose} className="text-zinc-400 hover:text-white p-1 rounded-lg">
                  <Icon name="x" className="h-5 w-5" />
                </button>
              </div>

              <div className="bg-[#0b0c0e] border-b border-zinc-800 flex text-xs font-mono">
                <button
                  onClick={() => setActiveTab('SCHEMA')}
                  className={`px-4 py-2.5 font-bold transition-colors ${
                    activeTab === 'SCHEMA'
                      ? 'bg-[#111215] text-amber-400 border-b-2 border-amber-400'
                      : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  MySQL DDL & Triggers
                </button>
                <button
                  onClick={() => setActiveTab('API')}
                  className={`px-4 py-2.5 font-bold transition-colors ${
                    activeTab === 'API'
                      ? 'bg-[#111215] text-amber-400 border-b-2 border-amber-400'
                      : 'text-zinc-400 hover:text-white'
                  }`}
                >
                  REST Endpoints Spec
                </button>
              </div>

              <div className="flex-1 p-5 overflow-y-auto font-mono text-xs text-zinc-300">
                {activeTab === 'SCHEMA' ? (
                  <pre className="bg-[#08090b] p-4 rounded-xl border border-zinc-800 overflow-x-auto text-amber-200/90 leading-relaxed">
                    {ddlSQL}
                  </pre>
                ) : (
                  <div className="space-y-4">
                    <div className="bg-[#08090b] p-3 rounded-lg border border-zinc-800">
                      <span className="text-emerald-400 font-bold">GET /api/v1/stock/alerts</span>
                      <p className="text-zinc-400 text-[11px] mt-1">Obtiene todos los productos bajo ROP calculados en tiempo real.</p>
                    </div>
                    <div className="bg-[#08090b] p-3 rounded-lg border border-zinc-800">
                      <span className="text-amber-400 font-bold">POST /api/v1/purchase-orders/auto-generate</span>
                      <p className="text-zinc-400 text-[11px] mt-1">Emite una O.C. automática hacia el portal del proveedor seleccionado.</p>
                    </div>
                  </div>
                )}
              </div>

              <div className="p-4 bg-[#0a0b0d] border-t border-zinc-800 flex justify-end">
                <button
                  onClick={onClose}
                  className="px-4 py-2 bg-amber-500 text-black font-bold text-xs rounded-lg hover:bg-amber-400"
                >
                  Cerrar Documentación
                </button>
              </div>
            </div>
          </div>
        );
      };

      // MAIN APP COMPONENT
      const App = () => {
        const [currentRole, setCurrentRole] = useState('GERENTE');
        const [proveedores, setProveedores] = useState(PROVEEDORES_INICIALES);
        const [productos, setProductos] = useState(PRODUCTOS_INICIALES);
        const [ordenesCompra, setOrdenesCompra] = useState(ORDENES_COMPRA_INICIALES);
        const [facturas, setFacturas] = useState(FACTURAS_INICIALES);
        const [selectedProveedorId, setSelectedProveedorId] = useState('prov-1');

        const [modalOrdenOpen, setModalOrdenOpen] = useState(false);
        const [alertaParaOrden, setAlertaParaOrden] = useState(null);
        const [specsOpen, setSpecsOpen] = useState(false);

        const alertas = useMemo(() => {
          return calcularAlertasStock(productos, proveedores);
        }, [productos, proveedores]);

        const resumenCP = useMemo(() => {
          const facturasVisibles = currentRole === 'PROVEEDOR'
            ? facturas.filter(f => f.proveedorId === selectedProveedorId)
            : facturas;
          return calcularResumenCuentasPagar(facturasVisibles);
        }, [facturas, currentRole, selectedProveedorId]);

        const ordenesActivasCount = useMemo(() => {
          return ordenesCompra.filter(o => 
            o.estado !== 'CANCELADA' && 
            (currentRole === 'GERENTE' || o.proveedorId === selectedProveedorId)
          ).length;
        }, [ordenesCompra, currentRole, selectedProveedorId]);

        const proveedorActual = useMemo(() => {
          return proveedores.find(p => p.id === selectedProveedorId);
        }, [proveedores, selectedProveedorId]);

        const handleAjustarStock = (productoId, nuevoStock) => {
          setProductos(prev => prev.map(p => {
            if (p.id === productoId) {
              return { ...p, stockActual: nuevoStock };
            }
            return p;
          }));
        };

        const handleUpdateProducto = (productoActualizado) => {
          setProductos(prev => prev.map(p => p.id === productoActualizado.id ? productoActualizado : p));
        };

        const handleGenerarOrdenModal = (alerta) => {
          setAlertaParaOrden(alerta);
          setModalOrdenOpen(true);
        };

        const handleConfirmOrder = (nuevaOrden) => {
          setOrdenesCompra(prev => [nuevaOrden, ...prev]);
        };

        const handlePagarFactura = (facturaId, metodo) => {
          setFacturas(prev => prev.map(f => {
            if (f.id === facturaId) {
              return {
                ...f,
                estado: 'PAGADA',
                saldoPendienteCOP: 0,
                metodoPago: metodo,
                diasMora: 0
              };
            }
            return f;
          }));
        };

        return (
          <div className="min-h-screen bg-[#0c0d0e] flex flex-col">
            <Header
              currentRole={currentRole}
              setCurrentRole={setCurrentRole}
              selectedProveedorId={selectedProveedorId}
              setSelectedProveedorId={setSelectedProveedorId}
              proveedores={proveedores}
              alertasCount={alertas.length}
              onOpenSpecs={() => setSpecsOpen(true)}
              onLogout={() => {
                window.location.assign('index.php?action=logout');
              }}
            />

            <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
              <MetricCards
                currentRole={currentRole}
                alertas={alertas}
                totalDeuda={resumenCP.totalDeuda}
                totalVencido={resumenCP.totalVencido}
                facturasVencidasCount={resumenCP.facturasVencidasCount}
                facturasPendientesCount={resumenCP.facturasPendientesCount}
                ordenesActivasCount={ordenesActivasCount}
                proveedorActual={proveedorActual}
              />

              {currentRole === 'GERENTE' && (
                <StockAlertsSection
                  alertas={alertas}
                  onGenerarOrden={handleGenerarOrdenModal}
                  onAjustarStock={handleAjustarStock}
                />
              )}

              <InventoryManager
                productos={productos}
                proveedores={proveedores}
                currentRole={currentRole}
                selectedProveedorId={selectedProveedorId}
                onUpdateProducto={handleUpdateProducto}
                onAjustarStock={handleAjustarStock}
              />

              <AccountsPayableSection
                facturas={facturas}
                proveedores={proveedores}
                currentRole={currentRole}
                selectedProveedorId={selectedProveedorId}
                onPagarFactura={handlePagarFactura}
              />
            </main>

            <CreateOrderModal
              alerta={alertaParaOrden}
              isOpen={modalOrdenOpen}
              onClose={() => setModalOrdenOpen(false)}
              onConfirmOrder={handleConfirmOrder}
            />

            <TechnicalSpecsModal
              isOpen={specsOpen}
              onClose={() => setSpecsOpen(false)}
            />
          </div>
        );
      };

      // Render Dashboard
      const root = ReactDOM.createRoot(document.getElementById('root'));
      root.render(<App />);
    </script>
  </body>
</html>

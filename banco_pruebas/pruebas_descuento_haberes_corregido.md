
# 🧪 Pruebas de escritorio - Descuento de Haberes (versión corregida)

**Facturas iniciales cargadas:**

```sql
INSERT INTO factura (id, nro_factura, fecha_factura, total_facturado, condicion_venta, id_agente)
VALUES
(96, '10002-00012348', '2025-04-08', 351700.00, 'codigo_608', 50207),
(97, '00002-00012348', '2025-04-08', 250000.00, 'codigo_608', 50207);
```

**Cuotas generadas:**

| ID  | Factura | Nro Cuota | Monto     | Estado     | Vencimiento | Periodo |
|-----|---------|-----------|-----------|------------|-------------|---------|
| 131 | 96      | 1         | 175850.00 | pendiente  | 2025-05-08  | NULL    |
| 132 | 96      | 2         | 175850.00 | pendiente  | 2025-06-08  | NULL    |
| 133 | 97      | 1         | 250000.00 | pendiente  | 2025-05-08  | NULL    |

---

## ✅ PRUEBA 1 - Período: 2025-05-01 a 2025-05-31

- **Tope disponible:** $100.000
- Se empieza con cuota ID 131 → paga $100.000 de $175.850
- Resto $75.850 se reprograma → periodo 2025-06-08
- Cuota 133 (mismo vencimiento) no entra por tope

| ID  | Pagado     | Reprogramado | Estado       | Nuevo Periodo |
|-----|------------|--------------|--------------|----------------|
| 131 | $100000.00 | $75850.00    | reprogramada | 2025-06-08     |
| 133 | $0.00      | $250000.00   | reprogramada | 2025-06-08     |

---

## ✅ PRUEBA 2 - Período: 2025-06-01 a 2025-06-30

- **Tope disponible:** $100.000
- Cuota 131: paga $75.850 → se cancela
- Cuota 133: paga $24.150 → resta $225.850 → periodo 2025-07-08

| ID  | Pagado     | Reprogramado | Estado       | Nuevo Periodo |
|-----|------------|--------------|--------------|----------------|
| 131 | $175850.00 | $0.00        | pagada       | -              |
| 133 | $24150.00  | $225850.00   | reprogramada | 2025-07-08     |

---

## ✅ PRUEBA 3 - Período: 2025-07-01 a 2025-07-31

- Cuota 132 (nueva cuota) vence 2025-06-08 → entra
- Cuota 133 reprogramada también entra
- Orden:
  - Paga $100.000 a cuota 132 → queda $75.850
  - Cuota 133 no entra

| ID  | Pagado     | Reprogramado | Estado       | Nuevo Periodo |
|-----|------------|--------------|--------------|----------------|
| 132 | $100000.00 | $75850.00    | reprogramada | 2025-08-08     |
| 133 | $24150.00  | $225850.00   | reprogramada | 2025-07-08     |

---

## ✅ PRUEBA 4 - Período: 2025-08-01 a 2025-08-31

- Paga $75.850 restante de cuota 132 → se cancela
- Resta $24.150 → se paga a cuota 133 → queda $201.700 → periodo 2025-09-08

| ID  | Pagado     | Reprogramado | Estado       | Nuevo Periodo |
|-----|------------|--------------|--------------|----------------|
| 132 | $175850.00 | $0.00        | pagada       | -              |
| 133 | $48300.00  | $201700.00   | reprogramada | 2025-09-08     |

---

## ✅ PRUEBA 5 - Período: 2025-09-01 a 2025-09-30

- Paga $100.000 → resta $101.700 → periodo 2025-10-08

| ID  | Pagado     | Reprogramado | Estado       | Nuevo Periodo |
|-----|------------|--------------|--------------|----------------|
| 133 | $148300.00 | $101700.00   | reprogramada | 2025-10-08     |

---

## ✅ PRUEBA 6 - Período: 2025-10-01 a 2025-10-31

- Paga $100.000 → resta $1.700 → periodo 2025-11-08

---

## ✅ PRUEBA 7 - Período: 2025-11-01 a 2025-11-30

- Se completa cuota 133 → $250.000 pagados → `estado = pagada`

---

## ✅ Resultado Final

| ID  | Pagado     | Reprogramado | Estado  |
|-----|------------|--------------|---------|
| 131 | $175850.00 | $0.00        | pagada  |
| 132 | $175850.00 | $0.00        | pagada  |
| 133 | $250000.00 | $0.00        | pagada  |

**✔ Se respetó el tope mensual de $100.000 en cada ciclo.**  
**✔ Se procesaron todas las cuotas en orden por vencimiento.**

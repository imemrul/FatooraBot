# Active Context: TradeOS UAE

## Current State
Full analytics dashboard built with stat cards, SVG charts, top customers, low stock alerts, and payment reminders.

## Current Focus
- Dashboard API: single /api/dashboard endpoint returns all widget data
- DashboardService aggregates: stats, revenue trend, collection trend, top customers, low stock, reminders
- SVG charts rendered client-side (no chart library dependency)
- Revenue trend: 12-month rolling window with monthly totals
- Collection trend: 12-month rolling window of payment amounts
- Top customers: ranked by total invoiced with outstanding balance
- Low stock: products below threshold or out of stock
- Overdue/due-today lists with email + WhatsApp reminder actions
- All data tenant-isolated via company_id

## Recent Decisions
- No chart library — pure SVG polyline/polygon rendered via Vue render functions
- DashboardService delegates to PaymentReminderService for reminder data
- Revenue uses TO_CHAR for PostgreSQL month grouping
- Stats include daily_sales, monthly_revenue, monthly_collected, total_outstanding
- Top customers limited to 5, low stock limited to 10
- Dashboard endpoint has no specific permission — accessible to all authenticated+onboarded users

## Open Questions
- None for current phase

## Blockers
- None

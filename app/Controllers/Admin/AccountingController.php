<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Folio;

class AccountingController extends Controller
{
    private function dates(): array
    {
        return Accounting::range($this->input('from'), $this->input('to'));
    }

    private function requireManage(): void
    {
        if (!Auth::can('accounting.manage')) { http_response_code(403); echo $this->view('errors.403', [], 'admin'); exit; }
    }

    /* ---------------- Overview ---------------- */
    public function overview(): string
    {
        [$from, $to] = $this->dates();
        return $this->view('admin.accounting.overview', [
            'active' => 'accounting', 'pageTitle' => 'Accounting Overview',
            'from' => $from, 'to' => $to,
            'summary' => Accounting::summary($from, $to),
            'byRoom' => Accounting::revenueByRoomType($from, $to),
            'byProvider' => Accounting::revenueByProvider($from, $to),
            'byCategory' => Accounting::expensesByCategory($from, $to),
            'daily' => Accounting::dailyRevenue($from, $to),
        ], 'admin');
    }

    /* ---------------- Payments ledger ---------------- */
    public function ledger(): string
    {
        [$from, $to] = $this->dates();
        return $this->view('admin.accounting.ledger', [
            'active' => 'acc_ledger', 'pageTitle' => 'Payments Ledger',
            'from' => $from, 'to' => $to,
            'status' => $this->input('status'), 'provider' => $this->input('provider'),
            'rows' => Accounting::paymentsLedger($from, $to, $this->input('status'), $this->input('provider')),
        ], 'admin');
    }

    /** Record a manual payment (cash/bank/GCash on arrival) against a booking. */
    public function recordPayment(): string
    {
        $this->requirePost(); $this->requireManage();
        $ref = trim((string)$this->input('reference'));
        $booking = $ref ? Booking::findByReference($ref) : null;
        if (!$booking) { flash('error', 'Booking reference not found.'); redirect('/admin/accounting/ledger'); return ''; }
        $amount = (float)$this->input('amount');
        if ($amount <= 0) { flash('error', 'Enter a valid amount.'); redirect('/admin/accounting/ledger'); return ''; }
        $this->db->insert('payments', [
            'booking_id' => $booking['id'], 'provider' => $this->input('method', 'cash'),
            'method' => $this->input('method', 'cash'), 'amount' => $amount, 'currency' => 'PHP',
            'status' => 'paid', 'external_id' => 'MANUAL-' . strtoupper(bin2hex(random_bytes(3))),
            'payload' => json_encode(['recorded_by' => Auth::id(), 'note' => $this->input('note')]),
        ]);
        // Update booking payment status
        $paid = (float)$this->db->scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id=? AND status='paid'", [$booking['id']]);
        $ps = $paid >= (float)$booking['total'] ? 'paid' : 'partial';
        $this->db->update('bookings', ['payment_status' => $ps, 'status' => $ps==='paid'?'confirmed':$booking['status'], 'updated_at' => date('c')], ['id' => $booking['id']]);
        flash('success', 'Payment of ' . money($amount) . ' recorded for ' . $ref . '.');
        redirect('/admin/accounting/ledger');
        return '';
    }

    /* ---------------- Expenses ---------------- */
    public function expenses(): string
    {
        [$from, $to] = $this->dates();
        return $this->view('admin.accounting.expenses', [
            'active' => 'acc_expenses', 'pageTitle' => 'Expenses',
            'from' => $from, 'to' => $to, 'categoryId' => (int)$this->input('category_id') ?: null,
            'rows' => Accounting::expensesList($from, $to, (int)$this->input('category_id') ?: null),
            'categories' => Accounting::categories(),
            'total' => Accounting::expenses($from, $to),
            'canManage' => Auth::can('accounting.manage'),
        ], 'admin');
    }

    public function saveExpense(string $id): string
    {
        $this->requirePost(); $this->requireManage();
        $data = [
            'category_id' => (int)$this->input('category_id') ?: null,
            'description' => trim((string)$this->input('description')),
            'vendor' => $this->input('vendor'),
            'amount' => (float)$this->input('amount'),
            'expense_date' => $this->input('expense_date') ?: date('Y-m-d'),
            'payment_method' => $this->input('payment_method'),
            'reference' => $this->input('reference'),
            'notes' => $this->input('notes'),
        ];
        if ($data['description'] === '' || $data['amount'] <= 0) {
            flash('error', 'Description and a valid amount are required.');
            redirect('/admin/accounting/expenses'); return '';
        }
        if ($id === 'new') {
            $data['recorded_by'] = Auth::id();
            $this->db->insert('expenses', $data);
            flash('success', 'Expense recorded.');
        } else {
            $this->db->update('expenses', $data, ['id' => $id]);
            flash('success', 'Expense updated.');
        }
        redirect('/admin/accounting/expenses');
        return '';
    }

    public function deleteExpense(string $id): string
    {
        $this->requirePost(); $this->requireManage();
        $this->db->delete('expenses', ['id' => $id]);
        flash('success', 'Expense deleted.');
        redirect('/admin/accounting/expenses');
        return '';
    }

    /* ---------------- Refunds ---------------- */
    public function refunds(): string
    {
        [$from, $to] = $this->dates();
        return $this->view('admin.accounting.refunds', [
            'active' => 'acc_refunds', 'pageTitle' => 'Refunds',
            'from' => $from, 'to' => $to,
            'rows' => Accounting::refundsList($from, $to),
            'total' => Accounting::refunds($from, $to),
            'canManage' => Auth::can('accounting.manage'),
        ], 'admin');
    }

    public function saveRefund(): string
    {
        $this->requirePost(); $this->requireManage();
        $ref = trim((string)$this->input('reference'));
        $booking = $ref ? Booking::findByReference($ref) : null;
        if (!$booking) { flash('error', 'Booking reference not found.'); redirect('/admin/accounting/refunds'); return ''; }
        $amount = (float)$this->input('amount');
        if ($amount <= 0) { flash('error', 'Enter a valid refund amount.'); redirect('/admin/accounting/refunds'); return ''; }
        $this->db->insert('refunds', [
            'booking_id' => $booking['id'], 'amount' => $amount,
            'reason' => $this->input('reason'), 'method' => $this->input('method', 'bank'),
            'refunded_by' => Auth::id(),
        ]);
        $this->db->update('bookings', ['payment_status' => 'refunded', 'status' => 'cancelled', 'updated_at' => date('c')], ['id' => $booking['id']]);
        flash('success', 'Refund of ' . money($amount) . ' recorded for ' . $ref . '.');
        redirect('/admin/accounting/refunds');
        return '';
    }

    /* ---------------- Reports + CSV ---------------- */
    public function reports(): string
    {
        [$from, $to] = $this->dates();
        return $this->view('admin.accounting.reports', [
            'active' => 'acc_reports', 'pageTitle' => 'Financial Reports',
            'from' => $from, 'to' => $to,
            'summary' => Accounting::summary($from, $to),
            'byRoom' => Accounting::revenueByRoomType($from, $to),
            'byProvider' => Accounting::revenueByProvider($from, $to),
            'byCategory' => Accounting::expensesByCategory($from, $to),
            'daily' => Accounting::dailyRevenue($from, $to),
        ], 'admin');
    }

    public function export(string $type): string
    {
        [$from, $to] = $this->dates();
        $filename = "rge-$type-$from-to-$to.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($type === 'expenses') {
            fputcsv($out, ['Date', 'Category', 'Description', 'Vendor', 'Method', 'Amount']);
            foreach (Accounting::expensesList($from, $to) as $r) {
                fputcsv($out, [$r['expense_date'], $r['category_name'], $r['description'], $r['vendor'], $r['payment_method'], $r['amount']]);
            }
        } elseif ($type === 'pnl') {
            $s = Accounting::summary($from, $to);
            fputcsv($out, ['Profit & Loss', "$from to $to"]);
            fputcsv($out, ['Booking revenue', $s['booking_revenue']]);
            fputcsv($out, ['Other income', $s['other_income']]);
            fputcsv($out, ['Total income', $s['income']]);
            fputcsv($out, ['Less refunds', -$s['refunds']]);
            fputcsv($out, ['Less expenses', -$s['expenses']]);
            fputcsv($out, ['Net profit', $s['net']]);
            fputcsv($out, []);
            fputcsv($out, ['VAT (' . $s['vat']['rate'] . '%) on income', $s['vat']['vat']]);
            fputcsv($out, ['Net of VAT', $s['vat']['net']]);
        } else { // ledger
            fputcsv($out, ['Date', 'Reference', 'Guest', 'Provider', 'Status', 'Amount']);
            foreach (Accounting::paymentsLedger($from, $to) as $r) {
                fputcsv($out, [$r['created_at'], $r['reference'], $r['guest_name'], $r['provider'], $r['status'], $r['amount']]);
            }
        }
        fclose($out);
        exit;
    }

    /* ---------------- Printable invoice ---------------- */
    public function invoice(string $id): string
    {
        $b = $this->db->first('SELECT b.*, rt.name AS room_name FROM bookings b JOIN room_types rt ON rt.id=b.room_type_id WHERE b.id=?', [$id]);
        if (!$b) return $this->abort(404, 'Booking not found');
        $payments = $this->db->all('SELECT * FROM payments WHERE booking_id=? ORDER BY id', [$id]);
        $charges = Folio::charges((int) $b['id']);
        $folio = Folio::summary($b);
        return $this->view('admin.accounting.invoice', [
            'b' => $b, 'payments' => $payments,
            'charges' => $charges, 'folio' => $folio,
            'vat' => vat_breakdown($folio['grand_total']),
        ], 'print');
    }
}

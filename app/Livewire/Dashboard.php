<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\UploadBatch;
use App\Models\Payslip;

class Dashboard extends Component
{
    public function render()
    {
        $latestBatch = UploadBatch::with('payslips.signature')
            ->where('status', 'completed')
            ->latest()
            ->first();

        $latestBatchStats = null;
        if ($latestBatch) {
            $totalPayslips = $latestBatch->payslips->count();
            $signedPayslips = $latestBatch->payslips->filter(function($p) { return $p->signature !== null; })->count();

            $latestBatchStats = [
                'batch' => $latestBatch,
                'total' => $totalPayslips,
                'signed' => $signedPayslips,
                'pending' => $totalPayslips - $signedPayslips,
                'percentage' => $totalPayslips > 0 ? round(($signedPayslips / $totalPayslips) * 100) : 0
            ];
        }

        return view('livewire.dashboard', [
            'totalEmployees' => User::where('role', 'employee')->count(),
            'totalBatches' => UploadBatch::count(),
            'pendingSignatures' => Payslip::where('status', 'pending')->where('is_rectified', false)->count(),
            'recentBatches' => UploadBatch::with('uploader')->latest()->take(5)->get(),
            'latestBatchStats' => $latestBatchStats
        ])->layout('components.layouts.app', ['header' => 'Dashboard', 'title' => 'Dashboard - BonosWeb']);
    }
}

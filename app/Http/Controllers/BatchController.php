<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Batch;
use App\Models\Product;
use App\Services\YieldCalculatorService;

class BatchController extends Controller
{
    public function __construct(
        protected YieldCalculatorService $yieldService
    ) {}

    /**
     * Daftar Batch Produksi dengan Filter & Pencarian
     */
    public function index(Request $request): Response
    {
        $query = Batch::with('product');

        if ($request->filled('search')) {
            $query->where('batch_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $batches = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
            'filters' => $request->only(['search', 'status', 'product_id']),
            'products' => Product::all(),
            'counts' => [
                'total' => Batch::count(),
                'open' => Batch::where('status', 'OPEN')->count(),
                'close' => Batch::where('status', 'CLOSE')->count(),
            ],
        ]);
    }

    /**
     * Form Tambah Batch Baru
     */
    public function create(): Response
    {
        return Inertia::render('Batches/Create', [
            'products' => Product::all(),
        ]);
    }

    /**
     * Simpan Batch Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string|max:50|unique:batches,batch_number',
            'batch_size' => 'required|numeric|min:1',
            'current_stage' => 'required|string|in:mixing,filling,sekunder',
            'status' => 'required|string|in:OPEN,CLOSE,HOLD,REWORK',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Batch::create($validated);

        return redirect()->route('batches.index')->with('success', 'Batch baru berhasil didaftarkan!');
    }

    /**
     * Detail Pelacakan Pipeline Batch Lintas Divisi (Traceability)
     */
    public function show(int $id): Response
    {
        $batch = Batch::with([
            'product',
            'reportItems.report.plant',
            'reportItems.machine',
            'reportItems.downtimeLogs',
        ])->findOrFail($id);

        return Inertia::render('Batches/Show', [
            'batch' => $batch,
        ]);
    }

    /**
     * Update Status Batch
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $batch = Batch::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:OPEN,CLOSE,HOLD,REWORK',
            'current_stage' => 'required|string|in:mixing,filling,sekunder,completed',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'CLOSE' && $batch->status !== 'CLOSE') {
            $batch->close_date = now()->toDateString();
        }

        $batch->update($validated);
        $this->yieldService->syncBatchTotals($batch);

        return back()->with('success', 'Status batch berhasil diperbarui!');
    }
}


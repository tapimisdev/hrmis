<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\Bir2316Request;
use App\Http\Resources\Taxation\Bir2316Resource;
use App\Services\Taxation\Bir2316ExcelService;
use App\Services\Taxation\Bir2316GenerationService;
use App\Services\Taxation\Bir2316PdfService;
use App\Services\Taxation\Bir2316Service;
use Illuminate\Http\Request;

class Bir2316Controller extends Controller
{
    public function __construct(
        private readonly Bir2316Service $bir2316Service,
        private readonly Bir2316GenerationService $bir2316GenerationService,
        private readonly Bir2316PdfService $bir2316PdfService,
        private readonly Bir2316ExcelService $bir2316ExcelService,
    ) {}

    public function index(Request $request)
    {
        $payload = $this->bir2316Service->getPagePayload(
            $request->only(['taxable_year', 'employee_id', 'division_id', 'employment_type_id', 'status'])
        );

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json($payload);
        }

        return view('admin.pages.taxation.bir-2316.index', $payload);
    }

    public function generate(Bir2316Request $request)
    {
        return response()->json(
            $this->bir2316GenerationService->generate($request->validated(), $request->user()?->id),
            201
        );
    }

    public function show(int $id, Request $request)
    {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json(
                new Bir2316Resource($this->bir2316Service->findOrFail($id))
            );
        }

        $this->bir2316Service->findOrFail($id);

        return view('admin.pages.taxation.bir-2316.show', [
            'recordId' => $id,
        ]);
    }

    public function lock(int $id, Request $request)
    {
        $record = $this->bir2316Service->lock(
            $this->bir2316Service->findOrFail($id),
            $request->user()?->id
        );

        return response()->json(new Bir2316Resource($record));
    }

    public function unlock(int $id, Request $request)
    {
        $record = $this->bir2316Service->unlock(
            $this->bir2316Service->findOrFail($id),
            $request->user()?->id
        );

        return response()->json(new Bir2316Resource($record));
    }

    public function print(int $id)
    {
        return $this->bir2316PdfService->inline(
            $this->bir2316Service->findOrFail($id)
        );
    }

    public function pdf(int $id)
    {
        return $this->bir2316PdfService->download($this->bir2316Service->findOrFail($id));
    }

    public function excel(int $id)
    {
        return $this->bir2316ExcelService->download($this->bir2316Service->findOrFail($id));
    }
}

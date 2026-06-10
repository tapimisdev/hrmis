<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Taxation\Bir2316Resource;
use App\Services\Taxation\Bir2316Service;
use Illuminate\Http\Request;

class Bir2316ApiController extends Controller
{
    public function __construct(
        private readonly Bir2316Service $bir2316Service
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->bir2316Service->getPagePayload(
                $request->only(['taxable_year', 'employee_id', 'division_id', 'employment_type_id', 'status'])
            )
        );
    }

    public function show(int $id)
    {
        return response()->json(
            new Bir2316Resource($this->bir2316Service->findOrFail($id))
        );
    }
}

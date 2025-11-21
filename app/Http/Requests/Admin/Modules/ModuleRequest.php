<?php

namespace App\Http\Requests\Admin\Modules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModuleRequest extends FormRequest
{
     public $module; // store full module record

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $slug = $this->route('slug');
        $this->module = DB::table('modules')
            ->where('slug', $slug)
            ->where('isActive', true)
            ->first();

        if (!$this->module) {
            abort(404);
        }

        $this->merge([
            'module_id' => $this->module->id
        ]);
    }

    public function rules()
    {
        $moduleId = $this->input('module_id');

        return [
            'tab_name' => ['required', 'string'],
            'tab_slug' => [
                'required',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('module_tabs')->where(fn($query) => $query->where('module_id', $moduleId)),
            ],
            'order' => [
                'required',
                'integer',
                Rule::unique('module_tabs')->where(fn($query) => $query->where('module_id', $moduleId)),
            ],
        ];
    }
}

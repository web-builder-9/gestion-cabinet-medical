<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SpecialtyRequest;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;

/**
 * Spécialités médicales (BF-25), affichées sur la page des paramètres.
 */
class SpecialtyController extends Controller
{
    public function store(SpecialtyRequest $request): RedirectResponse
    {
        $specialty = Specialty::create($request->validated());

        return redirect()->route('admin.settings')->with('status', "La spécialité « {$specialty->name} » a été ajoutée.");
    }

    public function update(SpecialtyRequest $request, Specialty $specialty): RedirectResponse
    {
        $specialty->update($request->validated());

        return redirect()->route('admin.settings')->with('status', "La spécialité « {$specialty->name} » a été modifiée.");
    }

    /**
     * RG-07 : refusé si un médecin a encore cette spécialité (Policy, et clé
     * étrangère restrictive en base en dernier rempart).
     */
    public function destroy(Specialty $specialty): RedirectResponse
    {
        if (! request()->user()->can('delete', $specialty)) {
            return redirect()->route('admin.settings')->with(
                'error',
                "La spécialité « {$specialty->name} » est attribuée à au moins un médecin : elle ne peut pas être supprimée."
            );
        }

        $specialty->delete();

        return redirect()->route('admin.settings')->with('status', "La spécialité « {$specialty->name} » a été supprimée.");
    }
}

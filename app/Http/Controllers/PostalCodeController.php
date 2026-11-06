<?php

namespace App\Http\Controllers;

use App\Support\PostalCodeLookup;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

class PostalCodeController extends Controller
{
    /**
     * A tela trata 404 e 503 como aviso: o preenchimento manual segue livre.
     */
    public function __invoke(string $postalCode, PostalCodeLookup $lookup): JsonResponse
    {
        try {
            $address = $lookup->find($postalCode);
        } catch (ConnectionException) {
            return response()->json(['message' => __('The postal code search is unavailable.')], 503);
        }

        return $address === null
            ? response()->json(['message' => __('Postal code not found.')], 404)
            : response()->json($address);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Community\VerifyLicense;
use Illuminate\Http\Request;

class VerifyLicenseController extends Controller
{
    public function form(Request $request)
    {
        if ($code = trim((string) $request->query('code'))) {
            return redirect()->route('verify.show', $code);
        }

        return view('verify.show', ['code' => null, 'result' => null]);
    }

    public function show(string $code, VerifyLicense $verify)
    {
        return view('verify.show', ['code' => $code, 'result' => $verify->handle($code)]);
    }

    public function api(string $code, VerifyLicense $verify)
    {
        $result = $verify->handle($code);

        return $result
            ? response()->json($result)
            : response()->json(['valid' => false, 'status' => 'not_found', 'purchase_code' => $code], 404);
    }
}

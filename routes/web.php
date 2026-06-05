<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/login', function () {
	if (Auth::check()) {
		return redirect('/telescope');
	}

	return response()->view('auth.telescope-login');
})->name('login');

Route::post('/login', function (Request $request) {
	$credentials = $request->validate([
		'email' => ['required', 'email'],
		'password' => ['required', 'string'],
	]);

	if (! Auth::attempt(array_merge($credentials, ['type_user' => 1]), $request->boolean('remember'))) {
		return back()->withErrors([
			'email' => 'Las credenciales no son válidas para acceso web.',
		])->onlyInput('email');
	}

	$request->session()->regenerate();

	return redirect('/telescope');
})->name('telescope.login');

Route::post('/logout', function (Request $request) {
	Auth::logout();

	$request->session()->invalidate();
	$request->session()->regenerateToken();

	return redirect()->route('login');
})->name('telescope.logout');

// Ruta de prueba para verificar la integración con OCI
// Route::get('/test-oci', function () {
//     $results = [];

//     // 1. Verificar configuración
//     $results['config'] = [
//         'driver' => config('filesystems.disks.oci.driver'),
//         'key' => config('filesystems.disks.oci.key') ? 'Configurado ✓' : 'NO configurado ✗',
//         'secret' => config('filesystems.disks.oci.secret') ? 'Configurado ✓' : 'NO configurado ✗',
//         'region' => config('filesystems.disks.oci.region'),
//         'bucket' => config('filesystems.disks.oci.bucket'),
//         'endpoint' => config('filesystems.disks.oci.endpoint'),
//     ];

//     // 2. Test de escritura
//     try {
//         Storage::disk('oci')->put('test.txt', 'Test file at ' . now());
//         $results['write_test'] = 'SUCCESS ✓';
//     } catch (\Exception $e) {
//         $results['write_test'] = 'ERROR ✗: ' . $e->getMessage();
//     }

//     // 3. Test de lectura
//     try {
//         if (Storage::disk('oci')->exists('test.txt')) {
//             $content = Storage::disk('oci')->get('test.txt');
//             $results['read_test'] = 'SUCCESS ✓';
//         }
//     } catch (\Exception $e) {
//         $results['read_test'] = 'ERROR ✗: ' . $e->getMessage();
//     }

//     // 4. Test de listado
//     try {
//         $files = Storage::disk('oci')->files();
//         $results['list_test'] = 'SUCCESS ✓ (' . count($files) . ' archivos)';
//         $results['files'] = $files;
//     } catch (\Exception $e) {
//         $results['list_test'] = 'ERROR ✗: ' . $e->getMessage();
//     }

//     // 5. Test de eliminación
//     try {
//         Storage::disk('oci')->delete('test.txt');
//         $results['delete_test'] = 'SUCCESS ✓';
//     } catch (\Exception $e) {
//         $results['delete_test'] = 'ERROR ✗: ' . $e->getMessage();
//     }

//     return response()->json($results, 200, [], JSON_PRETTY_PRINT);
// });
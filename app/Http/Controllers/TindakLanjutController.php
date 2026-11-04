<?php

namespace App\Http\Controllers;

use App\Models\EResep;
use App\Models\Kunjungan;
use App\Models\ParameterLoinc;
use App\Models\Pengguna;
use App\Models\RujukanLab;
use App\Models\RujukanLabParameter;
use App\Models\TindakLanjut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TindakLanjutController extends Controller
{
    private const PILIHAN_RESEP = 'resep';

    private const PILIHAN_RUJUKAN = 'rujukan';

    private const PILIHAN_TANPA_TINDAKAN = 'tanpa_tindakan';

    private const BENTUK_SEDIAAN = ['Tablet', 'Kapsul', 'Sirup'];

    public function store(Request $request, Kunjungan $kunjungan): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->isDokter()) {
            abort(403, 'Akses ditolak: hanya dokter yang berwenang mengelola tindak lanjut.');
        }

        $this->authorizeKunjungan($user, $kunjungan);

        if ($kunjungan->tindakLanjut()->exists()) {
            return $this->failure(
                $request,
                'Tindak lanjut untuk kunjungan ini sudah tersimpan.',
                409
            );
        }

        $finalisasi = $kunjungan->finalisasi()->first();
        if ($kunjungan->isFinalized() || $finalisasi?->isLocked()) {
            return $this->failure(
                $request,
                'Rekam medis sudah difinalisasi dan tidak dapat diubah.',
                403
            );
        }

        if (! $kunjungan->isSoapVerified()) {
            return $this->failure(
                $request,
                'Draf SOAP harus diverifikasi sebelum tindak lanjut disimpan.',
                422
            );
        }

        try {
            $validated = $this->validatePayload($request);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Lengkapi field wajib sebelum validasi.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            throw $exception;
        }

        $pilihan = array_values(array_unique($validated['opsi']));
        $punyaResep = in_array(self::PILIHAN_RESEP, $pilihan, true);
        $punyaRujukan = in_array(self::PILIHAN_RUJUKAN, $pilihan, true);
        $tanpaTindakan = in_array(self::PILIHAN_TANPA_TINDAKAN, $pilihan, true);

        $jenis = match (true) {
            $punyaResep && $punyaRujukan => TindakLanjut::JENIS_RESEP_RUJUKAN,
            $punyaResep => TindakLanjut::JENIS_RESEP,
            $punyaRujukan => TindakLanjut::JENIS_RUJUKAN,
            $tanpaTindakan => TindakLanjut::JENIS_TANPA_TINDAKAN,
            default => null,
        };

        if ($jenis === null) {
            return $this->failure(
                $request,
                'Pilih jenis tindak lanjut terlebih dahulu.',
                422
            );
        }

        try {
            $result = DB::transaction(function () use ($kunjungan, $jenis, $punyaResep, $punyaRujukan, $validated) {
                $locked = Kunjungan::whereKey($kunjungan->id_kunjungan)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->tindakLanjut()->exists()) {
                    throw new \RuntimeException('Tindak lanjut untuk kunjungan ini sudah tersimpan.');
                }

                $tindakLanjut = TindakLanjut::create([
                    'id_kunjungan' => $locked->id_kunjungan,
                    'jenis_tindak_lanjut' => $jenis,
                    'status' => TindakLanjut::STATUS_TERSIMPAN,
                ]);

                if ($punyaResep) {
                    foreach ($validated['resep'] as $obat) {
                        EResep::create([
                            'id_pasien' => $locked->id_pasien,
                            'id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut,
                            'nama_obat' => trim($obat['nama_obat']),
                            'dosis' => trim($obat['dosis']),
                            'bentuk_sediaan' => $obat['bentuk_sediaan'],
                            'aturan_konsumsi' => trim($obat['aturan_konsumsi']),
                            'jumlah' => (int) $obat['jumlah'],
                            'status' => 'Tersimpan',
                        ]);
                    }
                }

                if ($punyaRujukan) {
                    $parameter = ParameterLoinc::query()
                        ->whereKey($validated['id_parameter_loinc'])
                        ->where('is_active', true)
                        ->first();

                    if ($parameter === null) {
                        throw ValidationException::withMessages([
                            'id_parameter_loinc' => 'Parameter pemeriksaan laboratorium tidak valid.',
                        ]);
                    }

                    $rujukan = RujukanLab::create([
                        'id_tindak_lanjut' => $tindakLanjut->id_tindak_lanjut,
                        'catatan' => isset($validated['catatan_rujukan']) ? trim($validated['catatan_rujukan']) : null,
                        'status' => 'Tersimpan',
                    ]);

                    RujukanLabParameter::create([
                        'id_rujukan_lab' => $rujukan->id_rujukan_lab,
                        'id_parameter_loinc' => $parameter->id_parameter_loinc,
                        'nama_pemeriksaan' => $parameter->nama_pemeriksaan,
                        'kode_loinc' => $parameter->kode_loinc,
                    ]);
                }

                $statusKunjungan = $tindakLanjut->statusKunjungan();
                $locked->update(['status' => $statusKunjungan]);

                return [
                    'tindak_lanjut' => $tindakLanjut,
                    'status' => $statusKunjungan,
                ];
            });
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Lengkapi field wajib sebelum validasi.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $message = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Gagal menyimpan tindak lanjut. Silakan coba lagi.';

            return $this->failure($request, $message, 500);
        }

        $message = match ($jenis) {
            TindakLanjut::JENIS_RESEP => 'Resep obat berhasil disimpan.',
            TindakLanjut::JENIS_RUJUKAN => 'Rujukan lab berhasil disimpan.',
            TindakLanjut::JENIS_RESEP_RUJUKAN => 'Resep obat dan rujukan lab berhasil disimpan.',
            TindakLanjut::JENIS_TANPA_TINDAKAN => 'Rekam medis pasien telah ditandai selesai tanpa tindakan lanjutan.',
            default => 'Tindak lanjut berhasil disimpan.',
        };

        $redirect = route('dokter.rekam-medis', $kunjungan->id_antrean).'#finalisasi';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
                'status' => $result['status'],
                'jenis_tindak_lanjut' => $jenis,
            ], 201);
        }

        return redirect()->to($redirect)->with('status', $message);
    }

    private function validatePayload(Request $request): array
    {
        $opsi = $request->input('opsi', []);
        $opsi = is_array($opsi) ? array_values(array_unique($opsi)) : [];

        $punyaResep = in_array(self::PILIHAN_RESEP, $opsi, true);
        $punyaRujukan = in_array(self::PILIHAN_RUJUKAN, $opsi, true);
        $tanpaTindakan = in_array(self::PILIHAN_TANPA_TINDAKAN, $opsi, true);

        $rules = [
            'opsi' => ['required', 'array', 'min:1'],
            'opsi.*' => ['required', 'string', Rule::in([
                self::PILIHAN_RESEP,
                self::PILIHAN_RUJUKAN,
                self::PILIHAN_TANPA_TINDAKAN,
            ])],
        ];

        if ($punyaResep) {
            $rules += [
                'resep' => ['required', 'array', 'min:1'],
                'resep.*.nama_obat' => ['required', 'string', 'max:100'],
                'resep.*.dosis' => ['required', 'string', 'max:50'],
                'resep.*.bentuk_sediaan' => ['required', 'string', Rule::in(self::BENTUK_SEDIAAN)],
                'resep.*.jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
                'resep.*.aturan_konsumsi' => ['required', 'string', 'max:100'],
            ];
        }

        if ($punyaRujukan) {
            $rules += [
                'id_parameter_loinc' => [
                    'required',
                    'integer',
                    Rule::exists('parameter_loinc', 'id_parameter_loinc')
                        ->where(fn ($query) => $query->where('is_active', true)),
                ],
                'catatan_rujukan' => ['nullable', 'string', 'max:2000'],
            ];
        }

        $messages = [
            'opsi.required' => 'Pilih minimal satu jenis tindak lanjut.',
            'opsi.array' => 'Pilihan tindak lanjut tidak valid.',
            'opsi.*.required' => 'Pilihan tindak lanjut tidak valid.',
            'opsi.*.in' => 'Pilihan tindak lanjut tidak valid.',
            'resep.required' => 'Minimal satu obat wajib diisi.',
            'resep.array' => 'Data obat tidak valid.',
            'resep.min' => 'Minimal satu obat wajib diisi.',
            'resep.*.nama_obat.required' => 'Nama obat wajib diisi.',
            'resep.*.nama_obat.max' => 'Nama obat maksimal 100 karakter.',
            'resep.*.dosis.required' => 'Dosis wajib diisi.',
            'resep.*.dosis.max' => 'Dosis maksimal 50 karakter.',
            'resep.*.bentuk_sediaan.required' => 'Bentuk sediaan wajib dipilih.',
            'resep.*.bentuk_sediaan.in' => 'Bentuk sediaan harus Tablet, Kapsul, atau Sirup.',
            'resep.*.jumlah.required' => 'Jumlah obat wajib diisi.',
            'resep.*.jumlah.integer' => 'Jumlah obat harus berupa angka.',
            'resep.*.jumlah.min' => 'Jumlah obat minimal 1.',
            'resep.*.jumlah.max' => 'Jumlah obat maksimal 1000.',
            'resep.*.aturan_konsumsi.required' => 'Aturan minum wajib diisi.',
            'resep.*.aturan_konsumsi.max' => 'Aturan minum maksimal 100 karakter.',
            'id_parameter_loinc.required' => 'Parameter pemeriksaan laboratorium wajib dipilih.',
            'id_parameter_loinc.integer' => 'Parameter pemeriksaan laboratorium tidak valid.',
            'id_parameter_loinc.exists' => 'Parameter pemeriksaan laboratorium tidak valid.',
            'catatan_rujukan.max' => 'Catatan rujukan maksimal 2000 karakter.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($validator) use ($opsi, $tanpaTindakan) {
            if ($tanpaTindakan && count($opsi) > 1) {
                $validator->errors()->add(
                    'opsi',
                    'Pilihan Tanpa Tindakan Lanjut tidak dapat digabung dengan resep atau rujukan lab.'
                );
            }
        });

        return $validator->validate();
    }

    private function authorizeKunjungan(Pengguna $user, Kunjungan $kunjungan): void
    {
        if ($user->hospital_id) {
            $targetHospitalId = $kunjungan->hospital_id ?? $kunjungan->pasien?->hospital_id;

            if ($targetHospitalId && (int) $targetHospitalId !== (int) $user->hospital_id) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain.');
            }
        }

        if ($kunjungan->id_dokter === null) {
            $kunjungan->update(['id_dokter' => $user->id_pengguna]);

            return;
        }

        abort_unless(
            (int) $kunjungan->id_dokter === (int) $user->id_pengguna,
            403,
            'Kunjungan ini ditugaskan kepada dokter lain.'
        );
    }

    private function failure(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->withInput()->with('error', $message);
    }
}

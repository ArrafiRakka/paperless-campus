<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\EmissionFactor;
use App\Models\GeneratedDocument;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('paperless.demo.enabled')) return;
        $users = [
            ['name' => 'Rania Aditya', 'email' => 'pengaju@paperless.test', 'role' => 'pengaju', 'nim' => '23.11.0456', 'unit' => 'Sistem Informasi'],
            ['name' => 'Dimas Pratama', 'email' => 'verifikator@paperless.test', 'role' => 'verifikator', 'nim' => null, 'unit' => 'Unit Akademik'],
            ['name' => 'Nadia Kurnia', 'email' => 'admin@paperless.test', 'role' => 'admin', 'nim' => null, 'unit' => 'Unit Akademik'],
        ];
        foreach ($users as $user) User::query()->firstOrCreate(['email' => $user['email']], $user + ['password' => Hash::make('DemoPaperless!2026'), 'is_active' => true]);

        $types = [
            ['code' => 'active', 'name' => 'Surat Keterangan Aktif Kuliah', 'description' => 'Surat keterangan untuk keperluan akademik.', 'estimated_sheets' => 3, 'estimated_trips' => 2, 'approval_steps' => 1, 'is_active' => true, 'baseline_source'=>'Asumsi demo; belum divalidasi melalui observasi unit.', 'baseline_effective_at'=>'2026-11-17'],
            ['code' => 'event', 'name' => 'Surat Izin Kegiatan', 'description' => 'Permohonan izin kegiatan kampus.', 'estimated_sheets' => 10, 'estimated_trips' => 4, 'approval_steps' => 2, 'is_active' => true, 'baseline_source'=>'Asumsi demo; belum divalidasi melalui observasi unit.', 'baseline_effective_at'=>'2026-11-17'],
            ['code' => 'facility', 'name' => 'Peminjaman Fasilitas', 'description' => 'Permohonan penggunaan fasilitas kampus.', 'estimated_sheets' => 4, 'estimated_trips' => 3, 'approval_steps' => 1, 'is_active' => true, 'baseline_source'=>'Asumsi demo; belum divalidasi melalui observasi unit.', 'baseline_effective_at'=>'2026-11-17'],
        ];
        foreach ($types as $type) DocumentType::query()->firstOrCreate(['code' => $type['code']], $type);

        $factors = [
            ['code'=>'paper_mass_g','value'=>4.99,'unit'=>'g/lembar','source'=>'Kertas A4 80 gsm - asumsi laporan','source_year'=>2024,'description'=>'Massa selembar kertas A4 80 gsm.'],
            ['code'=>'paper_kg_co2e_per_kg','value'=>1.33932,'unit'=>'kgCO2e/kg kertas','source'=>'DESNZ conversion factors','source_year'=>2024,'description'=>'Faktor produksi kertas; cakupan perlu dikonfirmasi.'],
            ['code'=>'grid_kg_co2e_per_kwh','value'=>0.87,'unit'=>'kgCO2/kWh','source'=>'ESDM faktor grid Jamali - proxy laporan','source_year'=>2019,'description'=>'Proxy model; bukan lokasi perangkat/server terukur.'],
            ['code'=>'motor_kg_co2_per_litre','value'=>2.2565,'unit'=>'kgCO2/L','source'=>'Faktor laporan kelompok','source_year'=>2024,'description'=>'Faktor bahan bakar motor asumsi.'],
            ['code'=>'paper_cost_per_sheet','value'=>110,'unit'=>'IDR/lembar','source'=>'Asumsi biaya laporan','source_year'=>2026,'description'=>'Harga kertas simulasi.'],
            ['code'=>'printing_cost_per_page','value'=>200,'unit'=>'IDR/halaman','source'=>'Asumsi biaya laporan','source_year'=>2026,'description'=>'Biaya toner dan perawatan simulasi.'],
            ['code'=>'print_kwh_per_page','value'=>0.001,'unit'=>'kWh/halaman','source'=>'Asumsi laporan kelompok','source_year'=>2026,'description'=>'Energi pencetakan per halaman.'],
            ['code'=>'motor_km_per_litre','value'=>45,'unit'=>'km/L','source'=>'Asumsi laporan kelompok','source_year'=>2026,'description'=>'Efisiensi kendaraan motor.'],
            ['code'=>'motor_share','value'=>0.30,'unit'=>'fraksi','source'=>'Asumsi laporan kelompok','source_year'=>2026,'description'=>'Porsi perjalanan dengan motor.'],
            ['code'=>'trip_km_round_trip','value'=>2,'unit'=>'km/perjalanan','source'=>'Asumsi laporan kelompok','source_year'=>2026,'description'=>'Jarak pulang-pergi incremental.'],
            ['code'=>'residual_share','value'=>0.10,'unit'=>'fraksi','source'=>'Asumsi laporan kelompok','source_year'=>2026,'description'=>'Porsi proses fisik yang masih tersisa.'],
            ['code'=>'page_mb','value'=>0.35,'unit'=>'MB/kunjungan','source'=>'Target awal, belum diukur','source_year'=>2026,'description'=>'Transfer halaman target, bukan hasil pengukuran.'],
            ['code'=>'pdf_mb','value'=>0.15,'unit'=>'MB/dokumen','source'=>'Asumsi target, belum diukur','source_year'=>2026,'description'=>'Ukuran dokumen PDF target.'],
            ['code'=>'operational_kwh_per_gb','value'=>0.194,'unit'=>'kWh/GB','source'=>'SWDM v4 - faktor laporan','source_year'=>2024,'description'=>'Intensitas energi operasional asumsi.'],
            ['code'=>'embodied_kwh_per_gb','value'=>0.106,'unit'=>'kWh/GB','source'=>'SWDM v4 - faktor laporan','source_year'=>2024,'description'=>'Intensitas energi embodied asumsi.'],
            ['code'=>'global_embodied_kg_co2e_per_kwh','value'=>0.494,'unit'=>'kgCO2e/kWh','source'=>'Faktor laporan kelompok','source_year'=>2024,'description'=>'Faktor emisi embodied global asumsi.'],
        ];
        foreach ($factors as $factor) EmissionFactor::query()->firstOrCreate(['code' => $factor['code']], $factor);

        if (Submission::query()->exists()) return;
        $student = User::query()->where('email', 'pengaju@paperless.test')->firstOrFail();
        foreach ([
            ['code'=>'active','registration'=>'PLC-DEMO-0001','status'=>'completed','data'=>['full_name'=>$student->name,'nim'=>$student->nim,'program'=>$student->unit,'semester'=>5,'purpose'=>'Persyaratan beasiswa']],
            ['code'=>'event','registration'=>'PLC-DEMO-0002','status'=>'submitted','data'=>['event_name'=>'Seminar Teknologi Kampus','organizer'=>'Organisasi Mahasiswa','event_date'=>now()->addDays(20)->toDateString(),'location'=>'Auditorium','responsible'=>$student->name,'purpose'=>'Kegiatan demo']],
            ['code'=>'facility','registration'=>'PLC-DEMO-0003','status'=>'revision_requested','data'=>['facility'=>'Ruang Seminar B-204','event_name'=>'Diskusi Kampus','event_date'=>now()->addDays(30)->toDateString(),'start_time'=>'13:00','end_time'=>'16:00','participants'=>24,'contact'=>'081200000000','purpose'=>'Diskusi demo']],
        ] as $row) {
            $type = DocumentType::query()->where('code', $row['code'])->firstOrFail();
            $submission = Submission::create(['registration_number'=>$row['registration'],'user_id'=>$student->id,'document_type_id'=>$type->id,'data'=>$row['data'],'purpose'=>$row['data']['purpose'],'status'=>$row['status'],'current_step'=>$row['status']==='completed'?$type->approval_steps:1,'submitted_at'=>now(),'completed_at'=>$row['status']==='completed'?now():null]);
            SubmissionEvent::create(['submission_id'=>$submission->id,'actor_id'=>$student->id,'event'=>'submitted','metadata'=>['demo'=>true],'occurred_at'=>now()]);
            if ($row['status']==='completed') {
                SubmissionEvent::create(['submission_id'=>$submission->id,'actor_id'=>null,'event'=>'completed','metadata'=>['demo'=>true],'occurred_at'=>now()]);
                $docNumber='DOC-DEMO-'.str_pad((string)$submission->id,6,'0',STR_PAD_LEFT);
                $path='generated/'.$submission->id.'/'.$docNumber.'.pdf';
                $contents=app(\App\Services\MinimalPdfGenerator::class)->make(['PaperLess Campus - DATA DEMO','Nomor: '.$docNumber,'Registrasi: '.$submission->registration_number,'Layanan: '.$type->name,'Pemohon: '.$student->name,'Dokumen simulasi, bukan tanda tangan elektronik tersertifikasi.']);
                \Illuminate\Support\Facades\Storage::disk('local')->put($path,$contents);
                GeneratedDocument::create(['submission_id'=>$submission->id,'document_number'=>$docNumber,'pdf_path'=>$path,'sha256'=>hash('sha256',$contents),'generated_at'=>now()]);
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaperlessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'pengaju'): User
    {
        return User::create(['name' => ucfirst($role).' Demo', 'email' => $role.'-'.fake()->unique()->numberBetween(1, 999999).'@paperless.test', 'password' => 'DemoPassword!23', 'role' => $role, 'is_active' => true]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_logout_and_role_protection_use_database_user(): void
    {
        $user = User::where('email', 'pengaju@paperless.test')->firstOrFail();
        $this->post('/login', ['email' => $user->email, 'password' => 'DemoPaperless!2026'])->assertRedirect('/dashboard');
        $this->get('/admin/pengguna')->assertForbidden();
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_profile_can_be_updated_and_password_requires_current_password(): void
    {
        $user = User::where('email', 'pengaju@paperless.test')->firstOrFail();
        $this->actingAs($user)->get('/profil')->assertOk()->assertSee('Preferensi tampilan');
        $this->actingAs($user)->put('/profil', ['name' => 'Pengaju Diperbarui'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Pengaju Diperbarui']);
        $this->actingAs($user)->put('/profil', ['name' => 'Pengaju Diperbarui', 'current_password' => 'salah', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'PasswordBaru!2026'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put('/profil', ['name' => 'Pengaju Diperbarui', 'current_password' => 'DemoPaperless!2026', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'PasswordBaru!2026'])->assertRedirect();
        $this->assertTrue(auth()->validate(['email' => $user->email, 'password' => 'PasswordBaru!2026']));
    }

    public function test_landing_login_and_role_pages_render_successfully(): void
    {
        $this->get('/')->assertOk()->assertSee('Administrasi kampus lebih');
        $this->get('/login')->assertOk()->assertSee('Masuk ke akunmu');
        $student=User::where('email','pengaju@paperless.test')->firstOrFail();
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Tersimpan di basis data');
        $this->get('/pengajuan')->assertOk();
        $this->get('/pengajuan/buat')->assertOk();
        $this->get('/arsip')->assertOk();
        $this->get('/dampak')->assertOk()->assertSee('Skenario tahunan');
        $this->get('/metodologi')->assertOk();
        $reviewer=User::where('email','verifikator@paperless.test')->firstOrFail();
        $this->actingAs($reviewer)->get('/verifikasi')->assertOk();
        $admin=User::where('email','admin@paperless.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
        $this->get('/admin/layanan')->assertOk();
        $this->get('/admin/faktor-emisi')->assertOk();
    }

    public function test_admin_factor_change_is_audited_and_non_admin_is_forbidden(): void
    {
        $factor=\App\Models\EmissionFactor::where('code','grid_kg_co2e_per_kwh')->firstOrFail();
        $admin=User::where('email','admin@paperless.test')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.factors.update',$factor),['value'=>0.9,'unit'=>'kgCO2/kWh','source'=>'Sumber uji','source_year'=>2025,'description'=>'Nilai test','note'=>'Pembaruan pengujian'])->assertRedirect();
        $this->assertDatabaseHas('emission_factor_revisions',['emission_factor_id'=>$factor->id,'old_value'=>0.87,'new_value'=>0.9,'note'=>'Pembaruan pengujian']);
        $verifier=User::where('email','verifikator@paperless.test')->firstOrFail();
        $this->actingAs($verifier)->put(route('admin.factors.update',$factor),['value'=>0.91,'unit'=>'kgCO2/kWh'])->assertForbidden();
    }

    public function test_pengaju_can_save_draft_and_submit_persisted_application(): void
    {
        $user = $this->user();
        $payload = ['type'=>'active','full_name'=>'Sari Demo','nim'=>'991122','program'=>'Sistem Informasi','semester'=>4,'purpose'=>'Permohonan surat','intent'=>'draft'];
        $this->actingAs($user)->post('/pengajuan', $payload)->assertRedirect();
        $this->assertDatabaseHas('submissions', ['user_id'=>$user->id,'status'=>'draft']);
        $payload['intent']='submit';
        $this->actingAs($user)->post('/pengajuan', $payload)->assertRedirect();
        $this->assertDatabaseHas('submissions', ['user_id'=>$user->id,'status'=>'submitted']);
        $this->assertSame(2, Submission::where('user_id',$user->id)->distinct('registration_number')->count('registration_number'));
    }

    public function test_private_pdf_attachment_is_validated_and_owner_scoped(): void
    {
        Storage::fake('local');
        $owner=$this->user(); $other=$this->user();
        $payload=['type'=>'event','event_name'=>'Seminar','organizer'=>'Himpunan','event_date'=>now()->addDays(3)->toDateString(),'location'=>'Aula','responsible'=>'Sari','purpose'=>'Tujuan','intent'=>'submit','attachment'=>UploadedFile::fake()->create('lampiran.pdf',100,'application/pdf')];
        $this->actingAs($owner)->post('/pengajuan',$payload)->assertRedirect();
        $submission=Submission::where('user_id',$owner->id)->firstOrFail();
        $attachment=$submission->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->actingAs($other)->get(route('submissions.show',$submission))->assertForbidden();
        $this->actingAs($other)->get(route('submissions.attachment',[$submission,$attachment->id]))->assertForbidden();
        $payload['attachment']=UploadedFile::fake()->create('lampiran.txt',5,'text/plain');
        $this->actingAs($owner)->post('/pengajuan',$payload)->assertSessionHasErrors('attachment');
        $payload['attachment']=UploadedFile::fake()->create('besar.pdf',2049,'application/pdf');
        $this->actingAs($owner)->post('/pengajuan',$payload)->assertSessionHasErrors('attachment');
    }

    public function test_multistep_approval_records_actions_and_creates_private_internal_document_once(): void
    {
        Storage::fake('local');
        $student=$this->user('pengaju'); $verifier=$this->user('verifikator');
        $type=DocumentType::where('code','event')->firstOrFail();
        $submission=app(SubmissionWorkflow::class)->create($student,$type->id,['purpose'=>'Demo'],true);
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']))->assertRedirect();
        $submission->refresh();
        $this->assertSame('in_review',$submission->status);
        $this->assertSame(2,$submission->current_step);
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']))->assertRedirect();
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'completed']);
        $this->assertSame(2,GeneratedDocument::count());
        $doc=GeneratedDocument::where('submission_id',$submission->id)->firstOrFail();
        Storage::disk('local')->assertExists($doc->pdf_path);
        $this->assertStringEndsWith('.pdf',$doc->pdf_path);
        $this->assertStringStartsWith('%PDF-1.4',Storage::disk('local')->get($doc->pdf_path));
        $this->actingAs($student)->get(route('archive.download',$submission))->assertOk()->assertHeader('content-type','application/pdf');
        $this->actingAs($this->user())->get(route('archive.download',$submission))->assertForbidden();
    }

    public function test_reviewer_revision_requires_note_and_status_can_be_revised(): void
    {
        Storage::fake('local');
        $student=$this->user(); $reviewer=$this->user('verifikator');
        $type=DocumentType::where('code','active')->firstOrFail();
        $submission=app(SubmissionWorkflow::class)->create($student,$type->id,['purpose'=>'Demo'],true);
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'revision_requested']),['note'=>''])->assertSessionHasErrors('note');
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'revision_requested']),['note'=>'Mohon dilengkapi'])->assertRedirect();
        $this->assertDatabaseHas('approval_actions',['submission_id'=>$submission->id,'action'=>'revision_requested','note'=>'Mohon dilengkapi']);
        $this->actingAs($student)->put(route('submissions.update',$submission),['purpose'=>'Sudah dilengkapi','full_name'=>'Sari','nim'=>'11','program'=>'SI','semester'=>2])->assertRedirect();
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'submitted']);
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'approved']))->assertRedirect();
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'completed']);
    }
}

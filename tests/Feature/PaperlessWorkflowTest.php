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
use Illuminate\Support\Facades\Cache;
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

    public function test_railway_https_proxy_generates_secure_login_urls_redirects_and_session_cookie(): void
    {
        $host = 'paperless-campus-production.up.railway.app';
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => $host,
            'SERVER_NAME' => $host,
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
            'HTTP_X_FORWARDED_HOST' => $host,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ];
        config(['session.secure' => true, 'session.domain' => null, 'session.same_site' => 'lax']);

        $page = $this->withServerVariables($server)->get('/login')->assertOk()
            ->assertSee('action="https://'.$host.'/login"', false)
            ->assertSee('https://'.$host.'/paperless.css', false);
        $sessionCookie = collect($page->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertSame('lax', $sessionCookie->getSameSite());
        $this->assertNull($sessionCookie->getDomain());

        $this->withServerVariables($server)
            ->post('/login', ['email' => 'pengaju@paperless.test', 'password' => 'DemoPaperless!2026'])
            ->assertRedirect('https://'.$host.'/dashboard');
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

    public function test_impact_dashboard_is_cached_and_invalidates_after_baseline_change(): void
    {
        Cache::flush();
        $savings = app(\App\Services\SavingsService::class);
        $first = $savings->dashboard();
        $this->assertSame(1, $first['documents']);
        $this->assertCount(3, $first['by_type']);
        $this->assertCount(6, $first['trend']);
        $this->assertTrue(Cache::has('paperless.impact.unit'));

        DocumentType::where('code', 'active')->update(['estimated_sheets' => 7]);
        $this->assertSame($first['sheets'], $savings->dashboard()['sheets']);
        $savings->forget();
        $this->assertSame($first['sheets'] + 4, $savings->dashboard()['sheets']);
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
        $this->actingAs($owner)->get(route('submissions.show',$submission))->assertOk()->assertSee('lampiran.pdf')->assertSee(route('submissions.attachment',[$submission,$attachment->id]));
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
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']), ['step_number' => 1])->assertRedirect(route('reviews.show', $submission));
        $submission->refresh();
        $this->assertSame('in_review',$submission->status);
        $this->assertSame(2,$submission->current_step);
        $this->actingAs($verifier)->get(route('reviews.show', $submission))->assertOk()->assertSee('Dalam pemeriksaan')->assertSee('Setujui tahap');
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']), ['step_number' => 1])->assertStatus(409);
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']), ['step_number' => 2])->assertRedirect(route('reviews.show', $submission));
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'completed']);
        $this->assertSame(2,GeneratedDocument::count());
        $doc=GeneratedDocument::where('submission_id',$submission->id)->firstOrFail();
        Storage::disk('local')->assertExists($doc->pdf_path);
        $this->assertStringEndsWith('.pdf',$doc->pdf_path);
        $this->assertStringStartsWith('%PDF-1.4',Storage::disk('local')->get($doc->pdf_path));
        $this->actingAs($verifier)->get(route('reviews.show', $submission))->assertOk()->assertSee('Selesai')->assertSee('Mode baca saja')->assertDontSee('Setujui tahap');
        $this->actingAs($verifier)->post(route('reviews.decide',[$submission,'approved']), ['step_number' => 2])->assertForbidden();
        $this->assertSame(2, $submission->approvals()->count());
        $this->actingAs($student)->get(route('archive.download',$submission))->assertOk()->assertHeader('content-type','application/pdf');
        $this->actingAs($this->user())->get(route('archive.download',$submission))->assertForbidden();
    }

    public function test_reviewer_revision_requires_note_and_status_can_be_revised(): void
    {
        Storage::fake('local');
        $student=$this->user(); $reviewer=$this->user('verifikator');
        $type=DocumentType::where('code','active')->firstOrFail();
        $submission=app(SubmissionWorkflow::class)->create($student,$type->id,['purpose'=>'Demo'],true);
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'revision_requested']),['step_number'=>1,'note'=>''])->assertSessionHasErrors('note');
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'revision_requested']),['step_number'=>1,'note'=>'Mohon dilengkapi'])->assertRedirect();
        $this->actingAs($reviewer)->get(route('reviews.show', $submission))->assertOk()->assertSee('Menunggu revisi pengaju')->assertSee('Mode baca saja')->assertDontSee('Setujui tahap');
        $this->assertDatabaseHas('approval_actions',['submission_id'=>$submission->id,'action'=>'revision_requested','note'=>'Mohon dilengkapi']);
        $this->actingAs($student)->put(route('submissions.update',$submission),['purpose'=>'Sudah dilengkapi','full_name'=>'Sari','nim'=>'11','program'=>'SI','semester'=>2])->assertRedirect();
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'submitted']);
        $this->actingAs($reviewer)->post(route('reviews.decide',[$submission,'approved']), ['step_number' => 1])->assertRedirect();
        $this->assertDatabaseHas('submissions',['id'=>$submission->id,'status'=>'completed']);
    }

    public function test_reviewer_can_reject_with_a_note_and_no_archive_pdf_is_created(): void
    {
        Storage::fake('local');
        $student = $this->user();
        $reviewer = $this->user('verifikator');
        $type = DocumentType::where('code', 'active')->firstOrFail();
        $submission = app(SubmissionWorkflow::class)->create($student, $type->id, ['purpose' => 'Demo'], true);

        $this->actingAs($reviewer)->post(route('reviews.decide', [$submission, 'rejected']), ['step_number' => 1, 'note' => 'Dokumen tidak memenuhi ketentuan'])->assertRedirect();
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('approval_actions', ['submission_id' => $submission->id, 'action' => 'rejected', 'note' => 'Dokumen tidak memenuhi ketentuan']);
        $this->assertDatabaseMissing('generated_documents', ['submission_id' => $submission->id]);
        $this->actingAs($reviewer)->get(route('reviews.show', $submission))->assertOk()->assertSee('Ditolak')->assertSee('Mode baca saja')->assertDontSee('Setujui tahap');
    }

    public function test_review_access_is_role_and_ownership_protected(): void
    {
        $student = $this->user('pengaju');
        $otherStudent = $this->user('pengaju');
        $reviewer = $this->user('verifikator');
        $type = DocumentType::where('code', 'active')->firstOrFail();
        $submission = app(SubmissionWorkflow::class)->create($student, $type->id, ['purpose' => 'Demo'], true);

        $this->actingAs($otherStudent)->get(route('reviews.show', $submission))->assertForbidden();
        $this->actingAs($student)->post(route('reviews.decide', [$submission, 'approved']), ['step_number' => 1])->assertForbidden();

        $ownSubmission = app(SubmissionWorkflow::class)->create($reviewer, $type->id, ['purpose' => 'Pengajuan verifikator'], true);
        $this->actingAs($reviewer)->get(route('reviews.show', $ownSubmission))->assertOk()->assertSee('Mode baca saja')->assertDontSee('Setujui tahap');
        $this->actingAs($reviewer)->post(route('reviews.decide', [$ownSubmission, 'approved']), ['step_number' => 1])->assertForbidden();
        $this->assertSame('submitted', $ownSubmission->fresh()->status);
    }
}

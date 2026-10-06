<?php

use App\Enums\ProjectPhase;
use App\Enums\RoundStatus;
use App\Models\ActivityEntry;
use App\Models\Design;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ReviewRound;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Proofmark\DesignFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TestImages;

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->admin()->create();
    $this->organization = Organization::factory()->for($this->admin->workspace)->create();
    $this->project = Project::factory()->for($this->organization)->phase(ProjectPhase::Proofmark)->create(['name' => 'Wedding cakes']);
});

function draftRoundFor(Project $project): ReviewRound
{
    return ReviewRound::factory()->for($project)->create();
}

function uploadDesign(ReviewRound $round, UploadedFile $file, string $title = 'Home, desktop'): TestResponse
{
    return test()->post(route('admin.designs.store', $round), ['title' => $title, 'image' => $file]);
}

test('an admin starts a draft round', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page->component('admin/proofmark/show')
            ->where('round', null)
            ->where('canStartRound', true));

    $this->post(route('admin.rounds.store', $this->project))
        ->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => 1]));

    $round = ReviewRound::sole();

    expect($round->number)->toBe(1)
        ->and($round->status)->toBe(RoundStatus::Draft)
        ->and($round->workspace_id)->toBe($this->admin->workspace_id)
        ->and(ActivityEntry::where('event', 'proofmark.round_created')->sole()->visible_to_client)->toBeFalse();
});

test('starting a round while a draft exists goes to that draft', function () {
    ReviewRound::factory()->for($this->project)->inReview()->create();
    $draft = draftRoundFor($this->project);

    $this->actingAs($this->admin)
        ->post(route('admin.rounds.store', $this->project))
        ->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => $draft->number]));

    expect(ReviewRound::count())->toBe(2);
});

test('PNG, JPG and WebP uploads are stored re-encoded on the private disk', function (string $type, string $mime) {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload($type, 120, 80))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.proofmark.show', [$this->project, 'round' => 1]));

    $design = Design::sole();

    expect($design->mime)->toBe($mime)
        ->and($design->width)->toBe(120)
        ->and($design->height)->toBe(80)
        ->and($design->path)->toStartWith("proofmark/{$this->admin->workspace_id}/")
        ->and($design->path)->not->toContain('design')
        ->and($design->original_name)->toBe("design.{$type}");

    Storage::disk('local')->assertExists($design->path);
    expect(getimagesizefromstring((string) Storage::disk('local')->get($design->path))['mime'])->toBe($mime)
        ->and($design->bytes)->toBe(Storage::disk('local')->size($design->path));
})->with([
    ['png', 'image/png'],
    ['jpg', 'image/jpeg'],
    ['webp', 'image/webp'],
]);

test('EXIF data and appended bytes are stripped', function () {
    $round = draftRoundFor($this->project);
    $file = UploadedFile::fake()->createWithContent('photo.jpg', TestImages::jpegWithSecrets());

    expect(file_get_contents($file->getRealPath()))->toContain('SECRET-GPS-MARKER');

    $this->actingAs($this->admin);
    uploadDesign($round, $file)->assertSessionHasNoErrors();

    $stored = (string) Storage::disk('local')->get(Design::sole()->path);

    expect($stored)->not->toContain('SECRET-GPS-MARKER')
        ->and($stored)->not->toContain('SECRET-PHP-MARKER')
        ->and($stored)->not->toContain('Exif');
});

test('wide images are scaled down to 2560 px', function () {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload('png', 2880, 300))->assertSessionHasNoErrors();

    expect(Design::sole())->width->toBe(2560)->height->toBe(267);
});

test('unsafe or unsupported files are refused', function (Closure $file, string $message) {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, $file())
        ->assertRedirect()
        ->assertSessionHasErrors('image');

    expect(session('errors')->first('image'))->toContain($message);

    expect(Design::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('/');
})->with([
    'SVG' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'PNG, JPG or WebP'],
    'PHP renamed to .png' => [fn () => UploadedFile::fake()->createWithContent('shell.png', '<?php system($_GET["c"]); ?>'), 'PNG, JPG or WebP'],
    'HTML renamed to .jpg' => [fn () => UploadedFile::fake()->createWithContent('page.jpg', '<html><script>alert(1)</script></html>'), 'PNG, JPG or WebP'],
    'GIF' => [fn () => TestImages::upload('gif'), 'PNG, JPG or WebP'],
    'too large a file' => [fn () => UploadedFile::fake()->create('huge.png', 9000, 'image/png'), 'larger than 8 MB'],
    'decompression bomb' => [fn () => UploadedFile::fake()->createWithContent('bomb.png', TestImages::pngClaiming(50_000, 50_000)), 'pixels on one side'],
    'too many pixels' => [fn () => UploadedFile::fake()->createWithContent('big.png', TestImages::pngClaiming(5000, 5000)), '16 megapixels'],
    'damaged PNG' => [fn () => UploadedFile::fake()->createWithContent('broken.png', TestImages::pngClaiming(100, 100)), 'could not be read'],
]);

test('a title is required', function () {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload(), title: '')->assertSessionHasErrors('title');
});

test('designs can be renamed, reordered and removed in a draft', function () {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload(), 'Home');
    uploadDesign($round, TestImages::upload(), 'Order page');
    [$home, $order] = $round->designs()->get()->all();

    $this->patch(route('admin.designs.update', $home), ['title' => 'Home, desktop'])->assertRedirect();
    $this->post(route('admin.designs.move', $order), ['direction' => 'up'])->assertRedirect();

    expect($round->designs()->pluck('title')->all())->toBe(['Order page', 'Home, desktop']);

    $this->delete(route('admin.designs.destroy', $order))->assertRedirect();

    Storage::disk('local')->assertMissing($order->path);
    expect($round->designs()->pluck('title')->all())->toBe(['Home, desktop'])
        ->and(ActivityEntry::pluck('event')->all())->toContain('proofmark.design_renamed', 'proofmark.design_removed');
});

test('a sent round cannot be changed', function () {
    $round = ReviewRound::factory()->for($this->project)->inReview()->create();
    $design = Design::factory()->for($round, 'round')->create();

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload())->assertForbidden();
    $this->patch(route('admin.designs.update', $design), ['title' => 'New'])->assertForbidden();
    $this->delete(route('admin.designs.destroy', $design))->assertForbidden();

    expect(Design::count())->toBe(1);
});

test('another workspace gets 404 on rounds, designs and images', function () {
    $round = draftRoundFor($this->project);
    $design = Design::factory()->for($round, 'round')->create();
    $outsider = User::factory()->admin()->create();

    $this->actingAs($outsider);
    $this->get(route('admin.proofmark.show', $this->project))->assertNotFound();
    uploadDesign($round, TestImages::upload())->assertNotFound();
    $this->delete(route('admin.designs.destroy', $design))->assertNotFound();
    $this->get(route('designs.image', $design))->assertNotFound();
});

test('the image route serves the file to the admin with safe headers', function () {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload('webp'));

    $response = $this->get(route('designs.image', Design::sole()))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/webp')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
});

test('clients cannot see images of a draft round or another organization', function () {
    $draft = draftRoundFor($this->project);
    $draftDesign = Design::factory()->for($draft, 'round')->create();
    $sent = ReviewRound::factory()->for($this->project)->inReview()->create();
    $sentDesign = Design::factory()->for($sent, 'round')->create();
    Storage::disk('local')->put($sentDesign->path, TestImages::bytes());

    $client = User::factory()->client($this->organization)->create();
    $otherClient = User::factory()->client(Organization::factory()->for($this->admin->workspace)->create())->create();

    $this->actingAs($client)->get(route('designs.image', $draftDesign))->assertNotFound();
    $this->actingAs($client)->get(route('designs.image', $sentDesign))->assertOk();
    $this->actingAs($otherClient)->get(route('designs.image', $sentDesign))->assertNotFound();
    $this->actingAs($client)->get(route('admin.proofmark.show', $this->project))->assertForbidden();
});

test('guests are sent to the login page for images', function () {
    $design = Design::factory()->for(draftRoundFor($this->project), 'round')->create();

    $this->get(route('designs.image', $design))->assertRedirect(route('login'));
});

test('a sandbox cannot go over its upload quota', function () {
    config(['demo.upload_quota_bytes' => 1000]);
    $this->admin->workspace->update(['is_sandbox' => true, 'expires_at' => now()->addDay()]);
    $round = draftRoundFor($this->project);
    Design::factory()->for($round, 'round')->create(['bytes' => 900]);

    $this->actingAs($this->admin);
    uploadDesign($round, TestImages::upload('png', 200, 200))->assertSessionHasErrors('image');

    expect(session('errors')->first('image'))->toContain('This demo has used its');

    expect(Design::count())->toBe(1);
});

test('all sandboxes together cannot go over the total quota', function () {
    config(['demo.total_upload_quota_bytes' => 1000]);
    $this->admin->workspace->update(['is_sandbox' => true, 'expires_at' => now()->addDay()]);
    $otherSandbox = Workspace::factory()->sandbox()->create();
    $otherRound = ReviewRound::factory()->for(Project::factory()->for(Organization::factory()->for($otherSandbox)))->create();
    Design::factory()->for($otherRound, 'round')->create(['bytes' => 950]);

    $this->actingAs($this->admin);
    uploadDesign(draftRoundFor($this->project), TestImages::upload('png', 200, 200))
        ->assertSessionHasErrors('image');

    expect(session('errors')->first('image'))->toContain('run out of upload space');
});

test('real workspaces have no app-level quota', function () {
    config(['demo.upload_quota_bytes' => 10, 'demo.total_upload_quota_bytes' => 10]);

    $this->actingAs($this->admin);
    uploadDesign(draftRoundFor($this->project), TestImages::upload())->assertSessionHasNoErrors();
});

test('sandbox:prune deletes expired sandboxes uploads and orphaned folders', function () {
    $expired = Workspace::factory()->sandbox()->create(['expires_at' => now()->subHour()]);
    $active = Workspace::factory()->sandbox()->create(['expires_at' => now()->addHour()]);
    $disk = Storage::disk('local');

    $disk->put("proofmark/{$expired->id}/a.png", 'x');
    $disk->put("proofmark/{$active->id}/b.png", 'x');
    $disk->put("proofmark/{$this->admin->workspace_id}/c.png", 'x');
    $disk->put('proofmark/999999/orphan.png', 'x');

    $this->artisan('sandbox:prune')
        ->expectsOutputToContain('Deleted 1 expired sandbox(es) and 1 orphaned upload folder(s).')
        ->assertSuccessful();

    $disk->assertMissing("proofmark/{$expired->id}/a.png");
    $disk->assertMissing('proofmark/999999/orphan.png');
    $disk->assertExists("proofmark/{$active->id}/b.png");
    $disk->assertExists("proofmark/{$this->admin->workspace_id}/c.png");
});

test('the page lists rounds and the selected round designs', function () {
    ReviewRound::factory()->for($this->project)->inReview()->create();
    $draft = draftRoundFor($this->project);
    Design::factory()->for($draft, 'round')->create(['title' => 'Home, desktop', 'bytes' => 1_572_864]);

    $this->actingAs($this->admin)
        ->get(route('admin.proofmark.show', $this->project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rounds', 2)
            ->where('rounds.0.label', 'v2')
            ->where('round.statusLabel', 'Draft')
            ->where('round.designs.0.title', 'Home, desktop')
            ->where('round.designs.0.size', '1.5 MB')
            ->where('canStartRound', false)
            ->where('quota', null));

    $this->get(route('admin.proofmark.show', [$this->project, 'round' => 1]))
        ->assertInertia(fn (Assert $page) => $page->where('round.label', 'v1'));

    expect(DesignFiles::humanSize(20 * 1024 * 1024))->toBe('20 MB')
        ->and(DesignFiles::humanSize(15_781))->toBe('16 KB')
        ->and(DesignFiles::humanSize(0))->toBe('0 KB');
});

test('uploads are rate limited per user', function () {
    $round = draftRoundFor($this->project);

    $this->actingAs($this->admin);

    foreach (range(1, 20) as $i) {
        uploadDesign($round, UploadedFile::fake()->createWithContent('x.png', 'not an image'));
    }

    uploadDesign($round, TestImages::upload())->assertTooManyRequests();
});

<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Exceptions\DomainException;
use App\Models\OrderItem;
use App\Models\ResultAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imaging attachments (images and PDFs) live on the private disk under
 * results/{item} and are served only through authorized routes.
 */
final class StoreResultAttachmentAction
{
    public function execute(OrderItem $item, UploadedFile $file, ?User $actor = null): ResultAttachment
    {
        if (! in_array($item->status, [OrderItemStatus::Received, OrderItemStatus::Entered], true)) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        return DB::transaction(function () use ($item, $file, $actor): ResultAttachment {
            $path = $file->storeAs(
                "results/{$item->id}",
                'file-'.now()->timestamp.'-'.Str::random(6).'.'.$file->extension(),
                ['disk' => (string) config('center.storage_disk')],
            );

            if ($path === false) {
                throw DomainException::make('SERVER_ERROR', 500);
            }

            $attachment = ResultAttachment::query()->create([
                'order_item_id' => $item->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName() ?: 'attachment',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?? 0,
                'uploaded_by' => $actor?->id,
            ]);

            return $attachment;
        });
    }

    public function delete(ResultAttachment $attachment): void
    {
        Storage::disk((string) config('center.storage_disk'))->delete($attachment->path);
        $attachment->delete();
    }
}

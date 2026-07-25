<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function upload(string $attachableType, int $attachableId, UploadedFile $file): Attachment
    {
        $companyId = Auth::user()->company_id;
        $path = $file->store("attachments/{$companyId}", 'public');

        return Attachment::create([
            'company_id' => $companyId,
            'uploaded_by' => Auth::id(),
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function list(string $attachableType, int $attachableId): \Illuminate\Database\Eloquent\Collection
    {
        return Attachment::where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->where('company_id', Auth::user()->company_id)
            ->with('uploader:id,name')
            ->latest()
            ->get();
    }

    public function delete(Attachment $attachment): void
    {
        $attachment->deleteFile();
        $attachment->delete();
    }
}

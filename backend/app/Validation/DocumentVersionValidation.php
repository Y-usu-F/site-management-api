<?php
namespace App\Validation;
class DocumentVersionValidation
{
    public static function createRules(): array { return ['file_name'=>'required|string|max_length[255]','file_path'=>'required|string|max_length[500]','mime_type'=>'permit_empty|string|max_length[120]','size_bytes'=>'permit_empty|is_natural','checksum'=>'permit_empty|string|max_length[128]']; }
}

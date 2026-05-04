<?php

namespace App\Validation;

class AssetValidation
{
    public static function createRules(): array
    {
        return ['site_id' => 'required|is_natural_no_zero', 'block_id' => 'permit_empty|is_natural_no_zero', 'unit_id' => 'permit_empty|is_natural_no_zero', 'asset_no' => 'permit_empty|string|max_length[80]', 'asset_type' => 'required|in_list[elevator,generator,camera,fire_system,hydrophore,door_system,garden_equipment,cleaning_equipment,other]', 'name' => 'required|string|min_length[2]|max_length[160]', 'brand' => 'permit_empty|string|max_length[120]', 'model' => 'permit_empty|string|max_length[120]', 'serial_number' => 'permit_empty|string|max_length[120]', 'purchase_date' => 'permit_empty|valid_date[Y-m-d]', 'warranty_until' => 'permit_empty|valid_date[Y-m-d]', 'location_note' => 'permit_empty|string', 'status' => 'permit_empty|in_list[active,maintenance,broken,retired]'];
    }
    public static function updateRules(): array
    {
        return ['site_id' => 'permit_empty|is_natural_no_zero', 'block_id' => 'permit_empty|is_natural_no_zero', 'unit_id' => 'permit_empty|is_natural_no_zero', 'asset_no' => 'permit_empty|string|max_length[80]', 'asset_type' => 'permit_empty|in_list[elevator,generator,camera,fire_system,hydrophore,door_system,garden_equipment,cleaning_equipment,other]', 'name' => 'permit_empty|string|min_length[2]|max_length[160]', 'brand' => 'permit_empty|string|max_length[120]', 'model' => 'permit_empty|string|max_length[120]', 'serial_number' => 'permit_empty|string|max_length[120]', 'purchase_date' => 'permit_empty|valid_date[Y-m-d]', 'warranty_until' => 'permit_empty|valid_date[Y-m-d]', 'location_note' => 'permit_empty|string', 'status' => 'permit_empty|in_list[active,maintenance,broken,retired]'];
    }
}

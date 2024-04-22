<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabinet extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['cabinet_type', 'avatar_frame', 'logo', 'social_media_links'];

    /**
     * @param $data
     * @return mixed
     */
    public static function createCabinet($data): mixed
    {
        return self::create($data);
    }

    /**
     * @param $data
     * @return bool
     */
    public function updateCabinet($data): bool
    {
        return $this->update($data);
    }
}

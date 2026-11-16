<?php

namespace App\Models\Translation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTranslation extends Model
{
    use HasFactory;

    protected $table = 'app_translations';

    /**The table has no created_at / updated_at */
    public $timestamps = FALSE;

    protected $fillable = ['value', 'lang', 'translation',];
}

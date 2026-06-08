<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FtpServer extends Model
{
    protected $fillable = ['name', 'host', 'port', 'username', 'password', 'path', 'cfg_path', 'active'];

    protected $casts = [
        'password' => 'encrypted',
        'active'   => 'boolean',
        'port'     => 'integer',
    ];

    public function importedFiles(): HasMany
    {
        return $this->hasMany(FtpImportedFile::class);
    }
}
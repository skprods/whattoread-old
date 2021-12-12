<?php

namespace App\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FileManager
{
    public function saveBookFile(UploadedFile $file): string
    {
        $source = $file->getContent();
        $path = 'books';
        $filename = Hash::make($file->getClientOriginalName() . time());

        return $this->save($source, $path, $filename);
    }

    public function save(string $source, string $path, string $filename): string
    {
        $path = $this->prepareSlashes($path);

        Storage::put($path . $filename, $source);

        return $path . $filename;
    }

    private function prepareSlashes(string $path): string
    {
        /** Убираем / в начале пути, если он есть */
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }

        /** Добавляем / в конец пути, если его нет */
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return $path;
    }
}
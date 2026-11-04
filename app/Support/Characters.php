<?php
namespace App\Support;
class Characters
{
    public static function key(?string $key): string { return array_key_exists($key ?? '', config('characters')) ? $key : 'nala'; }
    public static function get(?string $key): array { return config('characters.'.self::key($key)); }
    public static function forBrowser(): array
    {
        $characters=config('characters');
        foreach($characters as &$character) $character['image']=asset('images/characters/'.$character['image']);
        unset($character); return $characters;
    }
}

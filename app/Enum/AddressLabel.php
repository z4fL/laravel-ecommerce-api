<?php

namespace App\Enum;

enum AddressLabel: string
{
    case RUMAH = 'rumah';
    case KANTOR = 'kantor';
    case KOS = 'kos';               // Boarding house
    case APARTEMEN = 'apartemen';   // Apartment
    case TOKO = 'toko';             // Shop / Store
    case GUDANG = 'gudang';         // Warehouse
    case LAINNYA = 'lainnya';       // Other / General fallback

    public function label(): string
    {
        return match ($this) {
            self::RUMAH => 'Rumah',
            self::KANTOR => 'Kantor',
            self::KOS => 'Kos',
            self::APARTEMEN => 'Apartemen',
            self::TOKO => 'Toko',
            self::GUDANG => 'Gudang',
            self::LAINNYA => 'Lainnya',
        };
    }
}

<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Cartera = 'cartera';
    case Coordinador = 'coordinador';
    case Docente = 'docente';
    case Consulta = 'consulta';
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Destino;

class DestinoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['codigo'=>'principal','nombre'=>'Área Principal','activo'=>true,'tipo'=>'general'],
            ['codigo'=>'acinf','nombre'=>'ACI Administración / Contaduría / Informática','activo'=>true,'tipo'=>'administrativo'],
            ['codigo'=>'agro','nombre'=>'Agro','activo'=>true,'tipo'=>'facultad'],
            ['codigo'=>'odontologia','nombre'=>'Odontología','activo'=>true,'tipo'=>'facultad'],
        ];
        foreach ($data as $d) {
            Destino::updateOrCreate(['codigo'=>$d['codigo']], $d);
        }
    }
}

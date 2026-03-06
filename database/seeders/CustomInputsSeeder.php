<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomInputsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('custom_inputs')->truncate();

        DB::table('custom_inputs')->insert([
        [
            'id' => 1,
            'kategori_id' => '1',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => 'Server,Ketikan Server,number',
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 2,
            'kategori_id' => '2',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 3,
            'kategori_id' => '3',
            'field_1' => 'UID,Ketikan UID,number',
            'field_2' => 'Server,Pilih Server,select',
            'field_select_title' => 'Asia,America,Europe,TWK_HK_MO',
            'field_select' => 'os_asia,os_usa,os_euro,os_cht'
        ],
        [
            'id' => 4,
            'kategori_id' => '4',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 5,
            'kategori_id' => '5',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 6,
            'kategori_id' => '6',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 7,
            'kategori_id' => '7',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 8,
            'kategori_id' => '8',
            'field_1' => 'ID,Ketikan ID,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 9,
            'kategori_id' => '9',
            'field_1' => 'Riot ID,Ketikan Riot ID,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 10,
            'kategori_id' => '10',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => 'Server,Ketikan Server,number',
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 11,
            'kategori_id' => '11',
            'field_1' => 'UID,Ketikan UID,number',
            'field_2' => 'Server,Pilih Server,select',
            'field_select_title' => 'Asia,America,Europe,TWK_HK_MO',
            'field_select' => 'os_asia,os_usa,os_euro,os_cht'
        ],
        [
            'id' => 12,
            'kategori_id' => '12',
            'field_1' => 'UID,Ketikan UID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 13,
            'kategori_id' => '13',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 14,
            'kategori_id' => '14',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => 'Server,Ketikan Server,number',
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 15,
            'kategori_id' => '15',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 16,
            'kategori_id' => '16',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 17,
            'kategori_id' => '17',
            'field_1' => ',,Select Input Type',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 18,
            'kategori_id' => '18',
            'field_1' => ',,Select Input Type',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 19,
            'kategori_id' => '19',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 20,
            'kategori_id' => '20',
            'field_1' => 'ID,Ketikan ID,number',
            'field_2' => 'Server,Pilih Server,select',
            'field_select_title' => 'Asia,NA and EU',
            'field_select' => '2001,2011'
        ],
        [
            'id' => 21,
            'kategori_id' => '21',
            'field_1' => 'No WhatsApp,Ketikan No,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 22,
            'kategori_id' => '22',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 23,
            'kategori_id' => '23',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 24,
            'kategori_id' => '24',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 25,
            'kategori_id' => '25',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 26,
            'kategori_id' => '26',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 27,
            'kategori_id' => '27',
            'field_1' => 'Nomor,Ketikan Nomor,number',
            'field_2' => 'Email,Ketikan Email,text',
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 28,
            'kategori_id' => '28',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 29,
            'kategori_id' => '29',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 30,
            'kategori_id' => '30',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 31,
            'kategori_id' => '31',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 32,
            'kategori_id' => '32',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 33,
            'kategori_id' => '33',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 34,
            'kategori_id' => '34',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 35,
            'kategori_id' => '35',
            'field_1' => 'Email,Ketikan Email,text',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 36,
            'kategori_id' => '36',
            'field_1' => 'Number Phone,0857******,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 37,
            'kategori_id' => '37',
            'field_1' => 'Nomor Telepon,0857******,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 38,
            'kategori_id' => '38',
            'field_1' => 'Nomor Telepon,082189093929,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        [
            'id' => 39,
            'kategori_id' => '39',
            'field_1' => 'ID :,Ketikan ID,number',
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null
        ],
        ]);
    }
}
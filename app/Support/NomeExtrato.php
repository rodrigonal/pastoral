<?php

namespace App\Support;

class NomeExtrato
{
    public static function formatar(string $nome): string
    {
        $nome = trim($nome);
        $nome = preg_replace('/\s+/', ' ', $nome) ?? $nome;

        $canonico = self::alias(mb_strtoupper($nome));
        if ($canonico !== null) {
            return $canonico;
        }

        return mb_convert_case(mb_strtolower($nome), MB_CASE_TITLE, 'UTF-8');
    }

    private static function alias(string $upper): ?string
    {
        $mapa = [
            'CASA A F PREDILETOS' => 'Casa A F Prediletos',
            'ARIANE GEISA CUTRIM C' => 'Ariane Geisa Cutrim',
            'MARIA APARECIDA CRUZ' => 'Maria Aparecida Cruz',
            'MARIA APARECIDA C BRI' => 'Maria Aparecida Cruz',
            'SANDRA REGINA J SALGA' => 'Sandra Regina J. Salgado',
            'SANDRA REGINA J SALGADO' => 'Sandra Regina J. Salgado',
            'MAURICIO MESQUITA COR' => 'Mauricio Mesquita Correa',
            'MAURICIO MESQUITA CORREA' => 'Mauricio Mesquita Correa',
            'ANTONIA LAURA COSTA G' => 'Antonia Laura Costa',
            'SANDRA EMILY LOPES SA' => 'Sandra Emily Lopes Sales',
            'SANDRA EMILY LOPES SALES' => 'Sandra Emily Lopes Sales',
            'ANTONIA CLARA FERREIR' => 'Antonia Clara Ferreira',
            'ANTONIA CLARA FERREIRA' => 'Antonia Clara Ferreira',
            'ESPACO LIMPO VARIEDAD' => 'Espaço Limpo Variedades',
            'ESPACO LIMPO VARIEDADES' => 'Espaço Limpo Variedades',
            'EMANOELLA REIS PEREIR' => 'Emanoella Reis Pereira',
            'EMANOELLA REIS PEREIRA' => 'Emanoella Reis Pereira',
            'ANTONIO BARTOLOMEU CO' => 'Antonio Bartolomeu Costa',
            'ANTONIO BARTOLOMEU COSTA' => 'Antonio Bartolomeu Costa',
            'ALDENORA ISABEL PEREI' => 'Aldenora Isabel Pereira',
            'ALDENORA ISABEL PEREIRA' => 'Aldenora Isabel Pereira',
            '11.551.184 CARLOS HEN' => 'Carlos Henrique',
            'CARLOS HENRIQUE (11.551.184)' => 'Carlos Henrique',
            'FRANCISCO OLIVEIRA VI' => 'Francisco Oliveira Viana',
            'FRANCISCO OLIVEIRA VIANA' => 'Francisco Oliveira Viana',
            'HYLLARY LARYSSA MACIE' => 'Hyllary Laryssa Maciel',
            'HYLLARY LARYSSA MACIEL' => 'Hyllary Laryssa Maciel',
            'ROMMEL BERNARDES ROCH' => 'Rommel Bernardes Rocha',
            'ROMMEL BERNARDES ROCHA' => 'Rommel Bernardes Rocha',
            'ANA PAULA SOUSA DE CA' => 'Ana Paula Sousa de Carvalho',
            'ANA PAULA SOUSA DE CARVALHO' => 'Ana Paula Sousa de Carvalho',
            'ALESSANDRA S PICANCO' => 'Alessandra S. Picanco',
            'JOSENITH BARROS CORRE' => 'Josenith Barros Correa',
            'JOSENITH BARROS CORREA' => 'Josenith Barros Correa',
            'MARIA DO PERPETUO SOC' => 'Maria do Perpetuo Socorro',
            'MARIA DO PERPETUO SOCORRO' => 'Maria do Perpetuo Socorro',
            'FATIMA D D D S BARRET' => 'Fatima D. S. Barreto',
            'FATIMA D D D S BARRETO' => 'Fatima D. S. Barreto',
            'RAFAELLA VIANA PEREIR' => 'Rafaella Viana Pereira',
            'RAFAELLA VIANA PEREIRA' => 'Rafaella Viana Pereira',
            'THAYLA THAIS GARCIA S' => 'Thayla Thais Garcia',
            'MARIA DA CONCEICAO AL' => 'Maria da Conceicao Alves',
            'MARIA DA CONCEICAO ALVES' => 'Maria da Conceicao Alves',
            'ACLESIA CATILANE GONC' => 'Aclesia Catilane Goncalves',
            'ACLESIA CATILANE GONCALVES' => 'Aclesia Catilane Goncalves',
            'RODRIGO NALBERTH CORR' => 'Rodrigo Nalberth Correa Rodrigues',
            'RODRIGO NALBERTH CORREA RODRIGUES' => 'Rodrigo Nalberth Correa Rodrigues',
            'JOSE DE RIBAMAR RIBEI' => 'Jose de Ribamar Ribeiro',
            'JOSE DE RIBAMAR RIBEIRO' => 'Jose de Ribamar Ribeiro',
            'FRANCISCA M SILV' => 'Francisca M. Silva',
            'FRANCISCA M SILVA' => 'Francisca M. Silva',
            'MARIA DAS DORES MARQU' => 'Maria das Dores Marques',
            'MARIA DAS DORES MARQUES' => 'Maria das Dores Marques',
            'KAMILA COSTA C FERNAN' => 'Kamila Costa C. Fernandes',
            'KAMILA COSTA C FERNANDES' => 'Kamila Costa C. Fernandes',
            'ANNA JESSICA BARROS C' => 'Anna Jessica Barros',
            'KEILA SILVA MOREIRA 0' => 'Keila Silva Moreira',
            'KEILA SILVA MOREIRA' => 'Keila Silva Moreira',
            'ASSAI ATACADISTA' => 'Assaí Atacadista',
            'ATACADAO CARAMBA' => 'Atacadão Caramba',
            'ATACADAO DOS TEMPEROS' => 'Atacadão dos Temperos',
            'MATEUS SUPERMERCADOS' => 'Mateus Supermercados',
            'INSTITUTO DOS POBRES' => 'Instituto dos Pobres',
            'INSTITUTO VM LENCOIS' => 'Instituto VM Lençois',
            'K C P COSTA COMERCIO' => 'K. C. P. Costa Comércio',
            'LIDERPLAST EMBALAGENS' => 'Liderplast Embalagens',
            'ILMO DE JESUS CAMPOS' => 'Ilmo de Jesus Campos',
            'ANA MARIA LUNA SOARES' => 'Ana Maria Luna Soares',
            'ARLEN COELHO COSTA' => 'Arlen Coelho Costa',
            'MARIA OZETE DE MELO' => 'Maria Ozete de Melo',
            'GUSTAVO CRUZ COELHO' => 'Gustavo Cruz Coelho',
            'ANA C BATISTA VERAS' => 'Ana C. Batista Veras',
            'T. L. PINTO' => 'T. L. Pinto',
        ];

        return $mapa[$upper] ?? null;
    }
}

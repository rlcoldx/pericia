<?php

namespace Agencia\Close\Models;

use Agencia\Close\Conn\Read;

class Company extends Model
{
    public function findDataCompany($company): Read
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios WHERE empresa = :empresa", "empresa={$company}");
        return $this->read;
    }

    public function findSlide($slug): Read
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT itens.*, slide.* FROM empresa_slide_itens as itens
                                        INNER JOIN empresa_slide as slide ON itens.id_slider = slide.id
                                        INNER JOIN usuarios as empresa ON empresa.id = slide.id_empresa
                                    WHERE empresa.slug = :slug AND slide.status = 'S'
                                    ORDER BY slide.id DESC, itens.id DESC", "slug={$slug}");
        return $this->read;
    }
}
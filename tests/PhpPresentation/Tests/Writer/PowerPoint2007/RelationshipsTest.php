<?php

namespace PhpOffice\PhpPresentation\Tests\Writer\PowerPoint2007;

use PhpOffice\PhpPresentation\Shape\Comment;
use PhpOffice\PhpPresentation\Tests\PhpPresentationTestCase;

class RelationshipsTest extends PhpPresentationTestCase
{
    public function testCommentsAuthors()
    {
        $oAuthor = new Comment\Author();
        $oComment = new Comment();
        $oComment->setAuthor($oAuthor);
        $this->oPresentation->getActiveSlide()->addShape($oComment);

        $this->assertZipXmlElementExists($this->oPresentation, 'PowerPoint2007', 'ppt/_rels/presentation.xml.rels', '/Relationships/Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/commentAuthors"]');
    }
}

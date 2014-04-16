<?php
/**
 * PHPWord
 *
 * @link        https://github.com/PHPOffice/PHPWord
 * @copyright   2014 PHPWord
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt LGPL
 */

namespace PhpOffice\PhpWord\Writer;

use PhpOffice\PhpWord\Element\Endnote;
use PhpOffice\PhpWord\Element\Footnote;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Object;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\PreserveText;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;

/**
 * HTML writer
 *
 * @since 0.10.0
 */
class HTML extends AbstractWriter implements WriterInterface
{
    /**
     * Is the current writer creating PDF?
     *
     * @var boolean
     */
    protected $isPdf = false;

    /**
     * Create new instance
     */
    public function __construct(PhpWord $phpWord = null)
    {
        $this->setPhpWord($phpWord);
    }

    /**
     * Save PhpWord to file
     *
     * @param string $filename
     * @throws Exception
     */
    public function save($filename = null)
    {
        if (!is_null($this->getPhpWord())) {
            $this->setTempDir(sys_get_temp_dir() . '/PHPWordWriter/');
            $hFile = fopen($filename, 'w') or die("can't open file");
            fwrite($hFile, $this->writeDocument());
            fclose($hFile);
            $this->clearTempDir();
        } else {
            throw new Exception("No PHPWord assigned.");
        }
    }

    /**
     * Get phpWord data
     *
     * @return string
     */
    public function writeDocument()
    {
        $html = '';
        $html .= '<!DOCTYPE html>' . PHP_EOL;
        $html .= '<!-- Generated by PHPWord -->' . PHP_EOL;
        $html .= '<html>' . PHP_EOL;
        $html .= '<head>' . PHP_EOL;
        $html .= $this->writeHTMLHead();
        $html .= '</head>' . PHP_EOL;
        $html .= '<body>' . PHP_EOL;
        $html .= $this->writeHTMLBody();
        $html .= '</body>' . PHP_EOL;
        $html .= '</html>' . PHP_EOL;

        return $html;
    }

    /**
     * Generate HTML header
     *
     * @return string
     */
    private function writeHTMLHead()
    {
        $properties = $this->getPhpWord()->getDocumentProperties();
        $propertiesMapping = array(
            'creator' => 'author',
            'title' => '',
            'description' => '',
            'subject' => '',
            'keywords' => '',
            'category' => '',
            'company' => '',
            'manager' => ''
        );
        $title = $properties->getTitle();
        $title = ($title != '') ? $title : 'PHPWord';

        $html = '';
        $html .= '<meta charset="UTF-8" />' . PHP_EOL;
        $html .= '<title>' . htmlspecialchars($title) . '</title>' . PHP_EOL;
        foreach ($propertiesMapping as $key => $value) {
            $value = ($value == '') ? $key : $value;
            $method = "get" . $key;
            if ($properties->$method() != '') {
                $html .= '<meta name="' . $value . '" content="' .
                    htmlspecialchars($properties->$method()) . '" />' . PHP_EOL;
            }
        }
        $html .= $this->writeStyles();

        return $html;
    }

    /**
     * Get content
     *
     * @return string
     */
    private function writeHTMLBody()
    {
        $phpWord = $this->getPhpWord();
        $html = '';

        $sections = $phpWord->getSections();
        $countSections = count($sections);
        $pSection = 0;

        if ($countSections > 0) {
            foreach ($sections as $section) {
                $pSection++;
                $contents = $section->getElements();
                foreach ($contents as $element) {
                    if ($element instanceof Text) {
                        $html .= $this->writeText($element);
                    } elseif ($element instanceof TextRun) {
                        $html .= $this->writeTextRun($element);
                    } elseif ($element instanceof Link) {
                        $html .= $this->writeLink($element);
                    } elseif ($element instanceof Title) {
                        $html .= $this->writeTitle($element);
                    } elseif ($element instanceof PreserveText) {
                        $html .= $this->writePreserveText($element);
                    } elseif ($element instanceof TextBreak) {
                        $html .= $this->writeTextBreak($element);
                    } elseif ($element instanceof PageBreak) {
                        $html .= $this->writePageBreak($element);
                    } elseif ($element instanceof Table) {
                        $html .= $this->writeTable($element);
                    } elseif ($element instanceof ListItem) {
                        $html .= $this->writeListItem($element);
                    } elseif ($element instanceof Image) {
                        $html .= $this->writeImage($element);
                    } elseif ($element instanceof Object) {
                        $html .= $this->writeObject($element);
                    } elseif ($element instanceof Endnote) {
                        $html .= $this->writeEndnote($element);
                    } elseif ($element instanceof Footnote) {
                        $html .= $this->writeFootnote($element);
                    }
                }
            }
        }

        return $html;
    }

    /**
     * Get text
     *
     * @param Text $text
     * @param boolean $withoutP
     * @return string
     */
    private function writeText($text, $withoutP = false)
    {
        $html = '';
        $paragraphStyle = $text->getParagraphStyle();
        $spIsObject = ($paragraphStyle instanceof Paragraph);
        $fontStyle = $text->getFontStyle();
        $sfIsObject = ($fontStyle instanceof Font);

        if ($paragraphStyle && !$withoutP) {
            $html .= '<p';
            if (!$spIsObject) {
                $html .= ' class="' . $paragraphStyle . '"';
            } else {
                $html .= ' style="' . $this->writeParagraphStyle($paragraphStyle) . '"';
            }
            $html .= '>';
        }
        if ($fontStyle) {
            $html .= '<span';
            if (!$sfIsObject) {
                $html .= ' class="' . $fontStyle . '"';
            } else {
                $html .= ' style="' . $this->writeFontStyle($fontStyle) . '"';
            }
            $html .= '>';
        }
        $html .= htmlspecialchars($text->getText());
        if ($fontStyle) {
            $html .= '</span>';
        }
        if ($paragraphStyle && !$withoutP) {
            $html .= '</p>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Get text run content
     *
     * @param TextRun $textrun
     * @return string
     */
    private function writeTextRun($textrun)
    {
        $html = '';
        $elements = $textrun->getElements();
        if (count($elements) > 0) {
            $paragraphStyle = $textrun->getParagraphStyle();
            $spIsObject = ($paragraphStyle instanceof Paragraph);
            $html .= '<p';
            if ($paragraphStyle) {
                if (!$spIsObject) {
                    $html .= ' class="' . $paragraphStyle . '"';
                } else {
                    $html .= ' style="' . $this->writeParagraphStyle($paragraphStyle) . '"';
                }
            }
            $html .= '>';
            foreach ($elements as $element) {
                if ($element instanceof Text) {
                    $html .= $this->writeText($element, true);
                } elseif ($element instanceof Link) {
                    $html .= $this->writeLink($element, true);
                } elseif ($element instanceof TextBreak) {
                    $html .= $this->writeTextBreak($element, true);
                } elseif ($element instanceof Image) {
                    $html .= $this->writeImage($element, true);
                } elseif ($element instanceof Endnote) {
                    $html .= $this->writeEndnote($element);
                } elseif ($element instanceof Footnote) {
                    $html .= $this->writeFootnote($element);
                }
            }
            $html .= '</p>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Write link
     *
     * @param Link $element
     * @param boolean $withoutP
     * @return string
     */
    private function writeLink($element, $withoutP = false)
    {
        $url = $element->getLinkSrc();
        $text = $element->getLinkName();
        $html = '';
        if (!$withoutP) {
            $html .= "<p>" . PHP_EOL;
        }
        $html .= "<a href=\"{$url}'\">{$text}</a>" . PHP_EOL;
        if (!$withoutP) {
            $html .= "</p>" . PHP_EOL;
        }

        return $html;
    }

    /**
     * Write heading
     *
     * @param Title $element
     * @return string
     */
    private function writeTitle($element)
    {
        $tag = 'h' . $element->getDepth();
        $text = htmlspecialchars($element->getText());
        $html = "<{$tag}>{$text}</{$tag}>" . PHP_EOL;

        return $html;
    }

    /**
     * Write preserve text
     *
     * @param PreserveText $element
     * @param boolean $withoutP
     * @return string
     */
    private function writePreserveText($element, $withoutP = false)
    {
        return $this->writeUnsupportedElement($element, $withoutP);
    }

    /**
     * Get text break
     *
     * @param TextBreak $element
     * @param boolean $withoutP
     * @return string
     */
    private function writeTextBreak($element, $withoutP = false)
    {
        if ($withoutP) {
            $html = '<br />' . PHP_EOL;
        } else {
            $html = '<p>&nbsp;</p>' . PHP_EOL;
        }

        return $html;
    }

    /**
     * Write page break
     *
     * @param PageBreak $element
     * @return string
     */
    private function writePageBreak($element)
    {
        return $this->writeUnsupportedElement($element, false);
    }

    /**
     * Write list item
     *
     * @param ListItem $element
     * @return string
     */
    private function writeListItem($element)
    {
        $text = htmlspecialchars($element->getTextObject()->getText());
        $html = '<p>' . $text . '</{$p}>' . PHP_EOL;

        return $html;
    }

    /**
     * Write table
     *
     * @param Table $element
     * @return string
     */
    private function writeTable($element)
    {
        $html = '';
        $rows = $element->getRows();
        $cRows = count($rows);
        if ($cRows > 0) {
            $html .= "<table>" . PHP_EOL;
            foreach ($rows as $row) {
                // $height = $row->getHeight();
                $rowStyle = $row->getStyle();
                $tblHeader = $rowStyle->getTblHeader();
                $html .= "<tr>" . PHP_EOL;
                foreach ($row->getCells() as $cell) {
                    $cellTag = $tblHeader ? 'th' : 'td';
                    $cellContents = $cell->getElements();
                    $html .= "<{$cellTag}>" . PHP_EOL;
                    if (count($cellContents) > 0) {
                        foreach ($cellContents as $content) {
                            if ($content instanceof Text) {
                                $html .= $this->writeText($content);
                            } elseif ($content instanceof TextRun) {
                                $html .= $this->writeTextRun($content);
                            } elseif ($content instanceof Link) {
                                $html .= $this->writeLink($content);
                            } elseif ($content instanceof PreserveText) {
                                $html .= $this->writePreserveText($content);
                            } elseif ($content instanceof TextBreak) {
                                $html .= $this->writeTextBreak($content);
                            } elseif ($content instanceof ListItem) {
                                $html .= $this->writeListItem($content);
                            } elseif ($content instanceof Image) {
                                $html .= $this->writeImage($content);
                            } elseif ($content instanceof Object) {
                                $html .= $this->writeObject($content);
                            } elseif ($element instanceof Endnote) {
                                $html .= $this->writeEndnote($element);
                            } elseif ($element instanceof Footnote) {
                                $html .= $this->writeFootnote($element);
                            }
                        }
                    } else {
                        $html .= $this->writeTextBreak(new TextBreak());
                    }
                    $html .= "</td>" . PHP_EOL;
                }
                $html .= "</tr>" . PHP_EOL;
            }
            $html .= "</table>" . PHP_EOL;
        }

        return $html;
    }

    /**
     * Write image
     *
     * @param Image $element
     * @param boolean $withoutP
     * @return string
     */
    private function writeImage($element, $withoutP = false)
    {
        $html = $this->writeUnsupportedElement($element, $withoutP);
        if (!$this->isPdf) {
            $imageData = $this->getBase64ImageData($element);
            if (!is_null($imageData)) {
                $style = $this->assembleCss(array(
                    'width' => $element->getStyle()->getWidth() . 'px',
                    'height' => $element->getStyle()->getHeight() . 'px',
                ));
                $html = "<img border=\"0\" style=\"{$style}\" src=\"{$imageData}\"/>";
                if (!$withoutP) {
                    $html = "<p>{$html}</p>" . PHP_EOL;
                }
            }
        }

        return $html;
    }

    /**
     * Write object
     *
     * @param Object $element
     * @param boolean $withoutP
     * @return string
     */
    private function writeObject($element, $withoutP = false)
    {
        return $this->writeUnsupportedElement($element, $withoutP);
    }

    /**
     * Write footnote
     *
     * @param Footnote $element
     * @return string
     */
    private function writeFootnote($element)
    {
        return $this->writeUnsupportedElement($element, true);
    }

    /**
     * Write endnote
     *
     * @param Endnote $element
     * @return string
     */
    private function writeEndnote($element)
    {
        return $this->writeUnsupportedElement($element, true);
    }

    /**
     * Write unsupported element
     *
     * @param mixed $element
     * @param boolean $withoutP
     * @return string
     */
    private function writeUnsupportedElement($element, $withoutP = false)
    {
        $elementClass = get_class($element);
        $elementMark = str_replace('PhpOffice\\PhpWord\\Element\\', '', $elementClass);
        $elementMark = htmlentities("<{$elementMark}>");
        if ($withoutP) {
            $html = "<span class=\"other-elm\">{$elementMark}</span>" . PHP_EOL;
        } else {
            $html = "<p>{$elementMark}</p>" . PHP_EOL;
        }

        return $html;
    }

    /**
     * Get styles
     *
     * @return string
     */
    private function writeStyles()
    {
        $bodyCss = array();
        $css = '<style>' . PHP_EOL;

        // Default styles
        $bodyCss['font-family'] = "'" . $this->getPhpWord()->getDefaultFontName() . "'";
        $bodyCss['font-size'] = $this->getPhpWord()->getDefaultFontSize() . 'pt';
        $css .= '* ' . $this->assembleCss($bodyCss, true) . PHP_EOL;

        // Custom styles
        $styles = Style::getStyles();
        if (is_array($styles)) {
            foreach ($styles as $name => $style) {
                if ($style instanceof Font) {
                    if ($style->getStyleType() == 'title') {
                        $name = str_replace('Heading_', 'h', $name);
                    } else {
                        $name = '.' . $name;
                    }
                    $css .= "{$name} " . $this->writeFontStyle($style, true) . PHP_EOL;
                } elseif ($style instanceof Paragraph) {
                    $name = '.' . $name;
                    $css .= "{$name} " . $this->writeParagraphStyle($style, true) . PHP_EOL;
                }
            }
        }
        $css .= '</style>' . PHP_EOL;

        return $css;
    }

    /**
     * Get font style
     *
     * @param Font $style
     * @param boolean $curlyBracket
     * @return string
     */
    private function writeFontStyle($style, $curlyBracket = false)
    {
        $css = array();
        if (PHPWord::DEFAULT_FONT_NAME != $style->getName()) {
            $css['font-family'] = "'" . $style->getName() . "'";
        }
        if (PHPWord::DEFAULT_FONT_SIZE != $style->getSize()) {
            $css['font-size'] = $style->getSize() . 'pt';
        }
        if (PHPWord::DEFAULT_FONT_COLOR != $style->getColor()) {
            $css['color'] = '#' . $style->getColor();
        }
        $css['background'] = $style->getFgColor();
        if ($style->getBold()) {
            $css['font-weight'] = 'bold';
        }
        if ($style->getItalic()) {
            $css['font-style'] = 'italic';
        }
        if ($style->getSuperScript()) {
            $css['vertical-align'] = 'super';
        } elseif ($style->getSubScript()) {
            $css['vertical-align'] = 'sub';
        }
        $css['text-decoration'] = '';
        if ($style->getUnderline() != Font::UNDERLINE_NONE) {
            $css['text-decoration'] .= 'underline ';
        }
        if ($style->getStrikethrough()) {
            $css['text-decoration'] .= 'line-through ';
        }

        return $this->assembleCss($css, $curlyBracket);
    }

    /**
     * Get paragraph style
     *
     * @param Paragraph $style
     * @param boolean $curlyBracket
     * @return string
     */
    private function writeParagraphStyle($style, $curlyBracket = false)
    {
        $css = array();
        if ($style->getAlign()) {
            $css['text-align'] = $style->getAlign();
        }

        return $this->assembleCss($css, $curlyBracket);
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string
     *
     * @param array $css
     * @param boolean $curlyBracket
     * @return string
     */
    private function assembleCss($css, $curlyBracket = false)
    {
        $pairs = array();
        foreach ($css as $key => $value) {
            if ($value != '') {
                $pairs[] = $key . ': ' . $value;
            }
        }
        $string = implode('; ', $pairs);
        if ($curlyBracket) {
            $string = '{ ' . $string . ' }';
        }

        return $string;
    }

    /**
     * Get Base64 image data
     *
     * @return string|null
     */
    private function getBase64ImageData(Image $element)
    {
        $imageData = null;
        $imageBinary = null;
        $source = $element->getSource();
        $imageType = $element->getImageType();

        // Get actual source from archive image
        if ($element->getSourceType() == Image::SOURCE_ARCHIVE) {
            $source = substr($source, 6);
            list($zipFilename, $imageFilename) = explode('#', $source);
            $zip = new \ZipArchive();
            if ($zip->open($zipFilename) !== false) {
                if ($zip->locateName($imageFilename)) {
                    $zip->extractTo($this->getTempDir(), $imageFilename);
                    $actualSource = $this->getTempDir() . DIRECTORY_SEPARATOR . $imageFilename;
                }
            }
            $zip->close();
        } else {
            $actualSource = $source;
        }

        // Read image binary data and convert into Base64
        if ($element->getSourceType() == Image::SOURCE_GD) {
            $imageResource = call_user_func($element->getImageCreateFunction(), $actualSource);
            ob_start();
            call_user_func($element->getImageFunction(), $imageResource);
            $imageBinary = ob_get_contents();
            ob_end_clean();
        } else {
            if ($fp = fopen($actualSource, 'rb', false)) {
                $imageBinary = fread($fp, filesize($actualSource));
                fclose($fp);
            }
        }
        if (!is_null($imageBinary)) {
            $base64 = chunk_split(base64_encode($imageBinary));
            $imageData = 'data:' . $imageType . ';base64,' . $base64;
        }

        return $imageData;
    }
}

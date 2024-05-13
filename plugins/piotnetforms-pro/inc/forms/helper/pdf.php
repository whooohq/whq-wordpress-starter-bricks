<?php

use psetasign\Fpdi;

require( __DIR__.'../../pdf/fpdf.php' );
require( __DIR__.'../../pdf/autoload.php' );

class Piotnetforms_PDF_Template extends Fpdi\Tfpdf\Fpdi {
	protected $B = 0;
	protected $I = 0;
	protected $U = 0;
	protected $HREF = '';
	public function WriteHTML( $html, $w ) {
		// HTML parser
		$html = str_replace( "\n", ' ', $html );
		$a = preg_split( '/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $a as $i=>$e ) {
			if ( $i%2==0 ) {
				// Text
				if ( $this->HREF ) {
					$this->PutLink( $this->HREF, $e );
				} else {
					//$this->MultiCell(70,5,$e);
					$this->Write( 5, $e );
				}
			} else {
				// Tag
				if ( $e[0]=='/' ) {
					$this->CloseTag( strtoupper( substr( $e, 1 ) ) );
				} else {
					// Extract attributes
					$a2 = explode( ' ', $e );
					$tag = strtoupper( array_shift( $a2 ) );
					$attr = [];
					foreach ( $a2 as $v ) {
						if ( preg_match( '/([^=]*)=["\']?([^"\']*)/', $v, $a3 ) ) {
							$attr[strtoupper( $a3[1] )] = $a3[2];
						}
					}
					$this->OpenTag( $tag, $attr );
				}
			}
		}
	}

	public function WriteHTML2( $html, $w, $x, $y, $p ) {
		// HTML parser
		$html = str_replace( "\n", ' ', $html );
		$a = preg_split( '/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
        if($p == 'J'){
            $this->SetXY( $x, $y );
        }else{
            $this->SetXY('',  $y );
        }
		foreach ( $a as $i=>$e ) {
			if ( $i%2==0 ) {
				// Text
				if ( $this->HREF ) {
					$this->PutLink( $this->HREF, $e );
				} else {
					$this->MultiCell( $w, 5, $e, 0, $p );
				}
			} else {
				// Tag
				if ( $e[0]=='/' ) {
					$this->CloseTag( strtoupper( substr( $e, 1 ) ) );
				} else {
					// Extract attributes
					$a2 = explode( ' ', $e );
					$tag = strtoupper( array_shift( $a2 ) );
					$attr = [];
					foreach ( $a2 as $v ) {
						if ( preg_match( '/([^=]*)=["\']?([^"\']*)/', $v, $a3 ) ) {
							$attr[strtoupper( $a3[1] )] = $a3[2];
						}
					}
					$this->OpenTag( $tag, $attr );
				}
			}
		}
	}

	public function OpenTag( $tag, $attr ) {
		// Opening tag
		if ( $tag=='B' || $tag=='I' || $tag=='U' ) {
			$this->SetStyle( $tag, true );
		}
		if ( $tag=='A' ) {
			$this->HREF = $attr['HREF'];
		}
		if ( $tag=='BR' ) {
			$this->Ln( 5 );
		}
	}

	public function CloseTag( $tag ) {
		// Closing tag
		if ( $tag=='B' || $tag=='I' || $tag=='U' ) {
			$this->SetStyle( $tag, false );
		}
		if ( $tag=='A' ) {
			$this->HREF = '';
		}
	}

	public function SetStyle( $tag, $enable ) {
		// Modify style and select corresponding font
		$this->$tag += ( $enable ? 1 : -1 );
		$style = '';
		foreach ( [ 'B', 'I', 'U' ] as $s ) {
			if ( $this->$s>0 ) {
				$style .= $s;
			}
		}
		$this->SetFont( '', $style );
	}

	public function PutLink( $URL, $txt ) {
		// Put a hyperlink
		$this->SetTextColor( 0, 0, 255 );
		$this->SetStyle( 'U', true );
		$this->Write( 5, $txt, $URL );
		$this->SetStyle( 'U', false );
		$this->SetTextColor( 0 );
	}
}

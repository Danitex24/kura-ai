<?php
/**
 * Handles the export functionality.
 *
 * @package    Kura_AI
 * @subpackage Kura_AI/includes
 * @author     Your Name <your-email@example.com>
 */
class Kura_AI_Export {

    /**
     * Generate a CSV file from an array of data.
     *
     * @since    1.0.0
     * @param    array     $data         The data to export.
     * @param    string    $filename     The name of the file.
     */
    public static function generate_csv( $data, $filename ) {
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $output = fopen( 'php://output', 'w' );

        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        fclose( $output );
        exit;
    }

    /**
     * Generate a PDF file from an array of data.
     *
     * @since    1.0.0
     * @param    array     $data         The data to export.
     * @param    string    $filename     The name of the file.
     */
    public static function generate_pdf( $data, $filename ) {
        // This is a placeholder. In a real application, you would use a PDF library like FPDF or TCPDF.
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        foreach ( $data as $row ) {
            echo implode( ', ', $row ) . "\n";
        }

        exit;
    }
}

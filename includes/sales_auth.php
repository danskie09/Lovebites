<?php
require_once dirname(__DIR__) . '/config/database.php';

class SalesReport {
    private $conn;
    
    public function __construct() {
        try {
            $db = new Database();
            $this->conn = $db->getConnection();
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Database connection error");
        }
    }
    
    public function getSales($start_date, $end_date) {
        try {
            // Validate dates
            if (!$this->validateDates($start_date, $end_date)) {
                throw new Exception("Invalid date range");
            }

            $stmt = $this->conn->prepare("
                SELECT 
                    s.id,
                    p.name as item_name,
                    s.quantity_sold,
                    s.price,
                    s.total_sales,
                    s.sale_date
                FROM sales s
                JOIN products p ON s.product_id = p.id
                WHERE s.sale_date BETWEEN :start_date AND :end_date 
                ORDER BY s.sale_date DESC
            ");
            
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = array_sum(array_column($sales, 'total_sales'));
            
            return ['sales' => $sales, 'total' => $total];
        } catch (PDOException $e) {
            error_log("Sales Query Error: " . $e->getMessage());
            throw new Exception("Error retrieving sales data");
        }
    }
    
    private function validateDates($start_date, $end_date) {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        return $start && $end && $start <= $end;
    }
    
    public function deleteSale($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM sales WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete Sale Error: " . $e->getMessage());
            throw new Exception("Error deleting sale");
        }
    }
    
    public function generatePDF($start_date, $end_date) {
        if (!class_exists('FPDF')) {
            require_once dirname(__DIR__) . '/vendor/fpdf/fpdf.php';
        }
        
        try {
            $sales_data = $this->getSales($start_date, $end_date);
            
            $pdf = new FPDF();
            $pdf->AddPage();
            
            // Header
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
            $pdf->Cell(0, 10, "Date Range: $start_date - $end_date", 0, 1, 'C');
            
            // Table header
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(60, 10, 'Item Name', 1);
            $pdf->Cell(40, 10, 'Quantity Sold', 1);
            $pdf->Cell(40, 10, 'Price (P)', 1);
            $pdf->Cell(50, 10, 'Total Sales (P)', 1);
            $pdf->Ln();
            
            // Table content
            $pdf->SetFont('Arial', '', 12);
            foreach ($sales_data['sales'] as $sale) {
                $pdf->Cell(60, 10, $sale['item_name'], 1);
                $pdf->Cell(40, 10, $sale['quantity_sold'], 1);
                $pdf->Cell(40, 10, number_format($sale['price'], 2), 1);
                $pdf->Cell(50, 10, number_format($sale['total_sales'], 2), 1);
                $pdf->Ln();
            }
            
            // Total
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(140, 10, 'Total Sales', 1);
            $pdf->Cell(50, 10, 'P ' . number_format($sales_data['total'], 2), 1);
            
            $pdf->Output('D', 'sales_report.pdf');
        } catch (Exception $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            throw new Exception("Error generating PDF report");
        }
    }
}
?>
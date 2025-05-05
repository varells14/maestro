<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Color;

class MaterialRequestController extends Controller
{
    public function request(Request $request)
    {
        $query = MaterialRequest::with('items')
        ->where('status', 'pending')
                    ->orderBy('created_at', 'desc');
    
        // Ambil tanggal dari request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
    
        // Terapkan filter tanggal jika ada
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
    
        // Ambil data setelah filter
        $requests = $query->get();
    
        // Cek jika ada permintaan export
        if ($request->has('format')) {
            return $this->exportData($request->format, $requests);
        }
    
        return view('user.material_request', compact('requests', 'startDate', 'endDate'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'request_name' => 'required|string|max:255',
            'priority' => 'required|string|in:Low,Medium,High,Urgent',
            'request_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        
       
        $requestNumber = $request->request_number;
        if (empty($requestNumber)) {
            $latestRequest = MaterialRequest::latest('id')->first();
            $nextId = $latestRequest ? $latestRequest->id + 1 : 1;
            $requestNumber = 'MR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }
        
        try {
             
            $materialRequest = MaterialRequest::create([
                'request_number' => $requestNumber,
                'request_name' => $request->request_name,
                'priority' => $request->priority,
                'request_date' => $request->request_date,
                'notes' => $request->notes,
                'status' => 'pending',  
                'checker' => 'pending',
                'approved' => 'waiting',
                
            ]);
            
            // Add the material items
            foreach ($request->items as $item) {
                MaterialRequestItem::create([
                    'request_id' => $materialRequest->id,
                    'product' => $item['product'],
                    'quantity' => $item['quantity'],
                ]);
            }
            
            return redirect()->route('user.material_request')->with('success', 'Material request submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('user.material_request')->with('error', 'Failed to submit material request: ' . $e->getMessage());
        }
    }

    
    
    public function getRequestDetails($id)
    {
        $request = MaterialRequest::with('items')->findOrFail($id);
        return view('user.material_request_details', compact('request'));
    }
    
    public function exportToExcel($id)
    {
        $request = MaterialRequest::with('items')->findOrFail($id);
    
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set column width
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        
        // Set row height for header
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(7)->setRowHeight(30);
        
        // ===== HEADER SECTION =====
        // Add logo
        try {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Company Logo');
            $drawing->setPath(public_path('assets/images/mes.png'));
            $drawing->setCoordinates('A2');
            $drawing->setHeight(80);
            $drawing->setWorksheet($sheet);
        } catch (\Exception $e) {
            // Logo optional - don't stop if there's an issue
        }
        
        // Company header
        $sheet->mergeCells('B2:F2');
        $sheet->setCellValue('B2', 'PT. KINARYA MAESTRO NUSANTARA');
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(16);
        
        $sheet->mergeCells('B3:F3');
        $sheet->setCellValue('B3', 'Jl. Tupai Raya PGA No.13, RT.01/RW.07, Meruyung');
        
        $sheet->mergeCells('B4:F4');
        $sheet->setCellValue('B4', 'Kec. Limo, Kota Depok, Jawa Barat 16515');
        
        $sheet->mergeCells('B5:F5');
        $sheet->setCellValue('B5', 'Phone: +62812-8721-6516 | Email: maestro@contractor.com');
        
        // Add horizontal line
        $sheet->getStyle('A6:F6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        
        // ===== TITLE SECTION =====
        $sheet->mergeCells('A8:F8');
        $sheet->setCellValue('A8', 'MATERIAL REQUEST DETAILS');
        $sheet->getStyle('A8')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A8')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
        $sheet->getStyle('A8:F8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // ===== REQUEST INFO SECTION =====
        // Style info box
        $sheet->getStyle('A10:F14')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
        $sheet->getStyle('A10:F14')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Request info fields
        $sheet->setCellValue('B10', 'Request Number:');
        $sheet->setCellValue('C10', $request->request_number);
        $sheet->getStyle('B10')->getFont()->setBold(true);
        
        $sheet->setCellValue('E10', 'Status:');
        $sheet->setCellValue('F10', ucfirst($request->status));
        $sheet->getStyle('E10')->getFont()->setBold(true);
        
        // Color for status
        if (!empty($request->status)) {
            $statusColors = [
                'pending' => 'FFC000',
                'checked' => '00B0F0',
                'approved' => '92D050',
                'rejected' => 'FF0000',
                'completed' => '92D050',
            ];
            
            if (isset($statusColors[strtolower($request->status)])) {
                $statusColor = $statusColors[strtolower($request->status)];
                $sheet->getStyle('F10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColor);
                $sheet->getStyle('F10')->getFont()->setBold(true);
                $sheet->getStyle('F10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
        
        $sheet->setCellValue('B11', 'Request Name:');
        $sheet->setCellValue('C11', $request->request_name);
        $sheet->getStyle('B11')->getFont()->setBold(true);
        
        $sheet->setCellValue('E11', 'Priority:');
        $sheet->setCellValue('F11', $request->priority);
        $sheet->getStyle('E11')->getFont()->setBold(true);
        
        // Color for priority
        if (!empty($request->priority)) {
            $priorityColors = [
                'low' => '92D050',
                'medium' => 'FFC000',
                'high' => 'FF0000',
            ];
            
            if (isset($priorityColors[strtolower($request->priority)])) {
                $priorityColor = $priorityColors[strtolower($request->priority)];
                $sheet->getStyle('F11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($priorityColor);
                $sheet->getStyle('F11')->getFont()->setBold(true);
                $sheet->getStyle('F11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
        
        $sheet->setCellValue('B12', 'Request Date:');
        $sheet->setCellValue('C12', $request->request_date ? \Carbon\Carbon::parse($request->request_date)->format('d M Y') : 'N/A');
        $sheet->getStyle('B12')->getFont()->setBold(true);
        
       
       
     
        
       
        $sheet->getStyle('B14')->getFont()->setBold(true);
        
        // ===== TABLE HEADER =====
        $sheet->setCellValue('A16', 'No');
        $sheet->setCellValue('B16', 'Material Name');
        $sheet->setCellValue('C16', 'Quantity');
        $sheet->setCellValue('D16', 'Unit');
        $sheet->setCellValue('E16', 'Notes');
        
        // Style the table header
        $sheet->getStyle('A16:F16')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0062B6');
        $sheet->getStyle('A16:F16')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A16:F16')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A16:F16')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(16)->setRowHeight(22);
        
        // ===== DATA ROWS =====
        $row = 17;
        $rowCount = count($request->items);
        
        foreach ($request->items as $index => $item) {
            // Set row height
            $sheet->getRowDimension($row)->setRowHeight(20);
            
            // Add zebra striping
            if ($index % 2 == 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF4FF');
            }
            
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->product);
            $sheet->setCellValue('C' . $row, $item->quantity ?? '-');
            $sheet->setCellValue('D' . $row, $item->unit ?? 'pcs');
            $sheet->setCellValue('E' . $row, $request->notes);
            
            $sheet->getStyle('A' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row . ':D' . $row)->getAlignment()->setWrapText(true);
            
            $row++;
        }
        
        // Add totals row
        $sheet->setCellValue('D' . $row, 'Total Items:');
        $sheet->setCellValue('E' . $row, $rowCount);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $row += 2;
        
        // ===== NOTES SECTION =====
        if (!empty($request->notes)) {
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('A' . $row, 'NOTES:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
            $sheet->getStyle('A' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $row++;
            
            $sheet->mergeCells('A' . $row . ':F' . ($row + 2));
            $sheet->setCellValue('A' . $row, $request->notes);
            $sheet->getStyle('A' . $row . ':F' . ($row + 2))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('A' . $row . ':F' . ($row + 2))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getRowDimension($row)->setRowHeight(50);
            
            $row += 4;
        } else {
            $row += 1;
        }
        
        // ===== APPROVAL SECTION =====
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'APPROVAL SECTION');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
        $sheet->getStyle('A' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $row++;
        
        // Create approval boxes
        $sheet->mergeCells('B' . $row . ':C' . $row);
        $sheet->setCellValue('B' . $row, 'REVIEWED BY');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
        $sheet->getStyle('B' . $row . ':C' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $sheet->mergeCells('D' . $row . ':F' . $row);
        $sheet->setCellValue('D' . $row, 'APPROVED BY');
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7EFFA');
        $sheet->getStyle('D' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Status boxes
        $sheet->mergeCells('B' . ($row + 1) . ':C' . ($row + 4));
        $checkerStatus = '';
        $checkerColor = 'FFFFFF';
        
        if ($request->checker) {
            if ($request->status == 'rejected' && isset($request->rejected_by) && $request->rejected_by == 'checker') {
                $checkerStatus = 'REJECTED';
                $checkerColor = 'FFCCCC';
            } else {
                $checkerStatus = 'REVIEWED';
                $checkerColor = 'E2EFDA';
            }
        }
        
        $sheet->setCellValue('B' . ($row + 1), $checkerStatus);
        $sheet->getStyle('B' . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B' . ($row + 1) . ':C' . ($row + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B' . ($row + 1) . ':C' . ($row + 4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($checkerColor);
        
        $sheet->mergeCells('D' . ($row + 1) . ':F' . ($row + 4));
        $approverStatus = '';
        $approverColor = 'FFFFFF';
        
        if ($request->approved) {
            if ($request->status == 'approved') {
                $approverStatus = 'APPROVED';
                $approverColor = 'E2EFDA';
            } else if ($request->status == 'rejected' && isset($request->rejected_by) && $request->rejected_by == 'approver') {
                $approverStatus = 'REJECTED';
                $approverColor = 'FFCCCC';
            }
        }
        
        $sheet->setCellValue('D' . ($row + 1), $approverStatus);
        $sheet->getStyle('D' . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D' . ($row + 1) . ':F' . ($row + 4))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D' . ($row + 1) . ':F' . ($row + 4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($approverColor);
        
        // Names & dates
        $sheet->mergeCells('B' . ($row + 5) . ':C' . ($row + 5));
        $sheet->setCellValue('B' . ($row + 5), $request->checker ?? '');
        $sheet->getStyle('B' . ($row + 5))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($row + 5))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B' . ($row + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('B' . ($row + 6) . ':C' . ($row + 6));
        $sheet->setCellValue('B' . ($row + 6), $request->checker_at ? \Carbon\Carbon::parse($request->checker_at)->format('d M Y') : '');
        $sheet->getStyle('B' . ($row + 6))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B' . ($row + 6))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('D' . ($row + 5) . ':F' . ($row + 5));
        $sheet->setCellValue('D' . ($row + 5), $request->approved ?? '');
        $sheet->getStyle('D' . ($row + 5))->getFont()->setBold(true);
        $sheet->getStyle('D' . ($row + 5))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D' . ($row + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->mergeCells('D' . ($row + 6) . ':F' . ($row + 6));
        $sheet->setCellValue('D' . ($row + 6), $request->approved_at ? \Carbon\Carbon::parse($request->approved_at)->format('d M Y') : '');
        $sheet->getStyle('D' . ($row + 6))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D' . ($row + 6))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // ===== FOOTER SECTION =====
        $row += 8;
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('A' . $row, 'Generated on: ' . date('d M Y H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Output file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Material_Request_' . str_pad($request->id, 4, '0', STR_PAD_LEFT) . '.xlsx';
        
        // Download response
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function history()
    {
        $query = MaterialRequest::with('items')
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('created_at', 'desc');
    
        // Ambil filter dari request
        $startDate = request('start_date');
        $endDate = request('end_date');
    
        // Terapkan filter jika tersedia
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
    
        $history = $query->get();
    
        // Export logic jika dibutuhkan
        if (request()->has('format')) {
            return $this->exportData(request('format'), $history);
        }
    
        return view('user.material_history', compact('history', 'startDate', 'endDate'));
    }
    
}
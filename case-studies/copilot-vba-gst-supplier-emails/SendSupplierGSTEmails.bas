Attribute VB_Name = "SupplierEmails"
Option Explicit

' Sends ONE consolidated GST-mismatch email per supplier.
' Data : sheet "Final for Mismatch Inv", Excel table "GST" (one row per invoice)
' Log  : sheet "Mail Log"
' Every column whose header starts with "To " or "CC " is used as a recipient.

Public Sub SendSupplierGSTEmails()
    Const SHEET_NAME As String = "Final for Mismatch Inv"
    Const TABLE_NAME As String = "GST"
    Const LOG_SHEET As String = "Mail Log"
    Const TEST_MODE As Boolean = True        ' True = open each email to check, False = send

    Dim tbl As ListObject, r As ListRow, firstRow As ListRow
    Dim groups As Object, key As Variant, lines As Collection
    Dim outlookApp As Object, mail As Object, logWS As Worksheet
    Dim html As String, grandTotal As Double, i As Long, logRow As Long, sent As Long

    Set tbl = ThisWorkbook.Worksheets(SHEET_NAME).ListObjects(TABLE_NAME)
    Set logWS = ThisWorkbook.Worksheets(LOG_SHEET)
    Set groups = CreateObject("Scripting.Dictionary")

    ' 1. Group the invoice lines by supplier code
    For Each r In tbl.ListRows
        key = CStr(Cell(tbl, r, "Supplier Code"))
        If Len(key) > 0 Then
            If Not groups.Exists(key) Then groups.Add key, New Collection
            groups(key).Add r
        End If
    Next r

    Set outlookApp = CreateObject("Outlook.Application")
    logRow = logWS.Cells(logWS.Rows.Count, 1).End(xlUp).Row + 1

    ' 2. Build one email per supplier
    For Each key In groups.Keys
        Set lines = groups(key)
        Set firstRow = lines(1)
        grandTotal = 0

        html = "<p>Dear " & Cell(tbl, firstRow, "Name of the Supplier") & " team,</p>" & _
               "<p>The invoices below do not match GSTR-2B for FY " & Cell(tbl, firstRow, "FY") & _
               ". Please review them and amend your GST return.</p>" & _
               "<table border='1' cellpadding='5' style='border-collapse:collapse;font-family:Calibri'>" & _
               "<tr style='background:#1F4E78;color:#fff'><th>Invoice No</th><th>Invoice Date</th>" & _
               "<th>Taxable Value</th><th>IGST</th><th>CGST</th><th>SGST</th><th>Total Tax</th></tr>"

        For i = 1 To lines.Count
            Set r = lines(i)
            html = html & "<tr><td>" & Cell(tbl, r, "Invoice No") & "</td>" & _
                   "<td>" & Format(Cell(tbl, r, "Invoice date"), "dd-mm-yyyy") & "</td>" & _
                   Amt(Cell(tbl, r, "Taxable value")) & Amt(Cell(tbl, r, "IGST")) & _
                   Amt(Cell(tbl, r, "CGST")) & Amt(Cell(tbl, r, "SGST")) & _
                   Amt(Cell(tbl, r, "Total tax")) & "</tr>"
            grandTotal = grandTotal + Cell(tbl, r, "Total tax")
        Next i

        html = html & "<tr style='font-weight:bold;background:#FFF2CC'><td colspan='6'>Grand total (" & _
               lines.Count & " invoices)</td>" & Amt(grandTotal) & "</tr></table>" & _
               "<p>Regards,<br>Finance team</p>"

        ' 3. Create the email
        Set mail = outlookApp.CreateItem(0)
        With mail
            .To = Recipients(tbl, firstRow, "To ")
            .CC = Recipients(tbl, firstRow, "CC ")
            .Subject = Cell(tbl, firstRow, "Mail Subject")
            .HTMLBody = html
            If TEST_MODE Then .Display Else .Send
        End With
        sent = sent + 1

        ' 4. Log it
        logWS.Cells(logRow, 1).Resize(1, 6).Value = Array(Now, key, _
            Cell(tbl, firstRow, "Name of the Supplier"), lines.Count, grandTotal, _
            IIf(TEST_MODE, "Displayed", "Sent"))
        logRow = logRow + 1
    Next key

    MsgBox sent & " supplier emails " & IIf(TEST_MODE, "opened for review.", "sent."), vbInformation
End Sub

' Value of a named column in a table row
Private Function Cell(tbl As ListObject, r As ListRow, colName As String) As Variant
    Cell = r.Range.Cells(1, tbl.ListColumns(colName).Index).Value
End Function

' Right-aligned amount cell
Private Function Amt(v As Variant) As String
    Amt = "<td align='right'>" & Format(v, "#,##0.00") & "</td>"
End Function

' Joins every non-blank column whose header starts with a prefix ("To " or "CC "), e.g. "To 1", "CC 2"
Private Function Recipients(tbl As ListObject, r As ListRow, prefix As String) As String
    Dim c As ListColumn, v As String
    For Each c In tbl.ListColumns
        If UCase(Left(c.Name, Len(prefix))) = UCase(prefix) Then
            v = Trim(CStr(r.Range.Cells(1, c.Index).Value))
            If Len(v) > 0 Then Recipients = Recipients & IIf(Len(Recipients) > 0, "; ", "") & v
        End If
    Next c
End Function

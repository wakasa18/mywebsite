param([string]$PdfPath)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Runtime.WindowsRuntime
[Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
[Windows.Data.Pdf.PdfDocument, Windows.Data.Pdf, ContentType = WindowsRuntime] | Out-Null
[Windows.Storage.Streams.InMemoryRandomAccessStream, Windows.Storage.Streams, ContentType = WindowsRuntime] | Out-Null
[Windows.Storage.Streams.DataReader, Windows.Storage.Streams, ContentType = WindowsRuntime] | Out-Null
function Wait-Result($Operation, [Type]$ResultType) {
    $method = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and $_.IsGenericMethod -and $_.GetGenericArguments().Count -eq 1 -and $_.GetParameters().Count -eq 1 } | Select-Object -First 1
    $task = $method.MakeGenericMethod($ResultType).Invoke($null, @($Operation))
    $task.Wait()
    return $task.Result
}
function Wait-Action($Operation) {
    $method = [System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object { $_.Name -eq 'AsTask' -and !$_.IsGenericMethod -and $_.GetParameters().Count -eq 1 } | Select-Object -First 1
    $task = $method.Invoke($null, @($Operation))
    $task.Wait()
}
$resolvedPdf = (Resolve-Path -LiteralPath $PdfPath).Path
$file = Wait-Result ([Windows.Storage.StorageFile]::GetFileFromPathAsync($resolvedPdf)) ([Windows.Storage.StorageFile])
$document = Wait-Result ([Windows.Data.Pdf.PdfDocument]::LoadFromFileAsync($file)) ([Windows.Data.Pdf.PdfDocument])
for ($pageIndex = 0; $pageIndex -lt $document.PageCount; $pageIndex++) {
    $page = $document.GetPage($pageIndex)
    $stream = New-Object Windows.Storage.Streams.InMemoryRandomAccessStream
    Wait-Action ($page.RenderToStreamAsync($stream))
    $reader = New-Object Windows.Storage.Streams.DataReader($stream.GetInputStreamAt(0))
    $loaded = Wait-Result ($reader.LoadAsync([uint32]$stream.Size)) ([uint32])
    $bytes = New-Object byte[] $loaded
    $reader.ReadBytes($bytes)
    $target = [System.IO.Path]::ChangeExtension($resolvedPdf, $null) + '-page-' + ($pageIndex + 1) + '.png'
    [System.IO.File]::WriteAllBytes($target, $bytes)
    $reader.Dispose(); $stream.Dispose(); $page.Dispose()
    Write-Output $target
}

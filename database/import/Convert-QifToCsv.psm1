# Autor: Daniel Marcoto, Anderson Silva
# Data: 28/05/2019
# Atualização: 05/08/2019
# ---------------------------------
# Script para extrair os dados de um arquivo QIF e salvar em formato CSV
# 
# O arquivo CSV resultante transformará cada registro em uma linha cujos dados estarão na seguinte ordem:
# - (D)ate
# - (T)Amount
# - (C)learedStatus
# - (P)ayee
# - (N)um
# - (L)Category
# - (M)emo
# 
# A primeira letra acima especifica a respectiva letra do arquivo QIF.
#
# Caso o arquivo esteja vazio 
#
# A especificação do QIF está no link: https://www.respmech.com/mym2qifw/qif_new.htm
#
# // primeiro é preciso importar o módulo
# Import-Module .\Convert-QifToCsv.psm1
# 
# // Examplo de linha para transformação
# Convert-QifToCsv -i example-01.qif -o example-01.csv
#
# Ajusta a saída para que tenha o formato para o centro de custo.
# ---------------------------------

function Convert-QifToCsv {
    param (
        [string]$i = "",
        [string]$o = ""
    )
    
    class Qif {
        [string]$Date
        [double]$TAmount
        [string]$ClearedStatus
        [string]$Payee
        [string]$Num
        [string]$LCategory
        [string]$Memo
        [string]$CostCenter
    }
    
    $inputFile = $i
    $outputFile = $o
    
    # Caso o arquivo de entrada não seja encontrado
    if (!(Test-Path $inputFile)) {
        Write-Output "Input File not found"
        return
    }

    $lines = New-Object System.Collections.ArrayList($null)

    $qifLine = [qif]::new() 
    
    Write-Output "Reading the QIF..."
    
    Get-Content $inputFile -Encoding UTF8 | ForEach-Object {
        
        if($_ -match $regex){
            
            if($_ -eq "^"){  
                $lines.Add($qifLine)
                
                $qifLine = [qif]::new()
            } elseif ($_ -eq "!Type:Cash") {
                # Start               
            } else {
                $firstLetter = $_.ToString().Substring(0,1)
                $value = $_.ToString().Substring(1)
                switch ($firstLetter) {
                    "D" {  
                        
                        $day = $value.ToString().Substring(0, $value.ToString().IndexOf("/"))
                        $month = $value.ToString().Substring($value.ToString().IndexOf("/") + 1,2).Replace("'", "")
                        $year = $value.ToString().Substring($value.ToString().IndexOf("'") + 1)                    
                        
                        if($year.Length -eq 2) {
                            $year = "20$year"
                        }

                        $qifLine.Date = Get-Date -Year $year -Month $month -Day $day -UFormat "%d/%m/%Y"
                    }
                    "T" {  
                        $qifLine.TAmount = $value
                    }
                    "C" {  
                        $qifLine.ClearedStatus = $value
                    }
                    "P" {  
                        $qifLine.Payee = $value
                    }
                    "N" {  
                        $qifLine.Num = $value
                    }
                    "L" {  

                        if ($qifLine.Payee -eq "Transferencia entre contas") {
                            return
                        }
                        # Category / Event
                        $item = $value.ToString().Split('/')
                        
                        # Categories
                        $categories = $item[0]
                        $category = $categories.ToString().Split(':')
                        $qifLine.LCategory = $category[0]

                        # Cost center
                        $costCenter = $item[1]
                        $qifLine.CostCenter = $costCenter

                        # Tags
                        #$eventTags = ''
                        #
                        #if ($events.Length -gt 0) {
                        #    $eventTags = $events.ToString().Split(':')
                        #}

                        #$eventTags += $categories.ToString().Replace(':', ' - ')
                        #$qifLine.CostCenter = $eventTags -join ','
                    }
                    "M" {  
                        $qifLine.Memo = $value
                    }
                    Default {
                        Write-Output "Failed. Letter '$firstLetter' is not recognized"
                        return
                    }
                }
                
            }        
            # Work here
            # Write-Output $value
        }
    }

    $lines.Add($qifLine)

    # Apaga o arquivo de saída caso exista
    if (Test-Path $outputFile) {
        Remove-Item $outputFile
    }
    
    Write-Output "Writing the CSV..."
    
    # Cria o arquivo de saída
    New-Item $outputFile -ItemType "file"
    
    foreach ($item in $lines) {
        $Date = $item.Date
        $TAmount = $item.TAmount
        $ClearedStatus = $item.ClearedStatus
        $Payee = $item.Payee
        $Num = $item.Num
        $LCategory = $item.LCategory
        $CostCenter = $item.CostCenter
        $Memo = $item.Memo
        
        # Formata na sequencia padronizada
        $line = "$date|$TAmount|$ClearedStatus|$Payee|$Num|$LCategory|$CostCenter|$Memo"
        
        # Adiciona a linha no arquivo de saída
        Add-Content $outputFile -Value $line -Encoding UTF8
    }
}

Export-ModuleMember -Function Convert-QifToCsv


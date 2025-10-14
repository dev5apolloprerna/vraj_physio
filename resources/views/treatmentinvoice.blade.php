

  
        
<style>

    table{
        width: 100%;
        /* border: 1px solid black; */
        font-family: sans-serif;
        border-collapse: collapse;
    
    }
    
    table th,
    td{
        padding: 5px;
        border: 1px solid #255a9b;
    }
    
    th{
        font-weight: bold;
    }
    </style>
    
    
    
    
    <table style="width:100%; text-align: start; border-bottom: 2px solid black;">
              <tr>
        <td rowspan="5" style="width: 56%; border:1px solid #fff; text-align:start;">
            <img style="text-align:start;" width="150" src="https://getdemo.in/vrajPhysio/img/logo.png" alt="">
        </td>
    
    </tr>
    <tr>
            <td style="border:1px solid #fff; width:44%; text-align:start;">
            <img width="16" src="https://getdemo.in/vrajPhysio/img/whatsapp.png" alt=""> +91 8866203090
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #fff; width:44%; text-align:start;font-size:12px;">
            <img width="16" src="https://getdemo.in/vrajPhysio/img/instagram.png" alt=""> Vraj physiotherapy & Child Development Center
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #fff; width:44%; text-align:start;font-size:12px;">
            <img width="16" src="https://getdemo.in/vrajPhysio/img/facebook.png" alt=""> Vraj Physiotherapy
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #fff; width:44%; text-align:start;font-size:12px;">
            <img width="16" src="https://getdemo.in/vrajPhysio/img/mail.png" alt=""> vrajphysiotherapyclinic@gmail.com
        </td>
    </tr>




        </table>
        
        <br/>
        <br/>
    
    
    <table style="width: 100%;">
        <tr>
            <td style="text-align: center; font-weight: 600;font-size:36px; padding-top: 10px;text-transform: uppercase; background: #255a9b;color: #fff;">Bill</td>
         </tr>
    </table>
    <table style="width: 100%; padding-bottom: 10px;">
        <tr>
            <td style="width: 50%;">Patient Name:-{{$Treatments[0]['patient_name'] }}</td>
            <td style="width: 50%;">Bill No:- {{$Treatments[0]['case_no']}}</td>
        </tr>
        <tr>
            <?php if(!empty($Treatments[0]['patient_address'])){
                ?>
            <td >Address:- : {{$Treatments[0]['patient_address']}}</td>
            <?php
            }else{
            ?>
            }else{
            ?>
                                    <td>Address : - </td>
            <?php }?>
    
                <td>Bill Date:- :{{ date('d-F-Y', strtotime($Treatments[0]['bill_date'])) }}</td>
        </tr>
        
    </table>
    <p><br></p>
    <table style="width: 100%;">
        
        <tr style="background-color: #255a9b; color: #fff;">
            <th style="width: 10%;">Sr No</th>
            <th style="width: 30%;">Treatment Type</th>
            <th style="width: 20%;">No Of Session</th>
            <th style="width: 10%;">Cost</th>
            <th style="width: 15%;">Discount</th>
            <th style="width: 15%;">Net Cost</th>
        </tr>
        <?php $i = 1; ?>
            @foreach ($Treatments as $treatments) 
    
        <tr>
            <td style="text-align: center;">{{ $i }}</td>
            <td style="text-align: center;">{{ $treatments['treatment_name'] }}</td>
            <td style=" text-align: center;">{{ $treatments['no_of_session'] }}</td>
            <td style=" text-align: center;">{{ $treatments['amount'] }}</td>
            <td style=" text-align: center;">{{ $treatments['discount_amount'] }}</td>
            <td style=" text-align: center;"> {{ $treatments['net_amount'] }}</td>
        </tr>
        <?php $i++; ?>
            @endforeach
        <tr>
            <td colspan="3" style=" text-align: start;text-transform: uppercase;">&nbsp;</td>
            <td colspan="2" style="text-align: center; ">TOTAL</td>
            <td style=" text-align: center;">{{$netAmount}}</td>
         </tr>
        <!-- <tr>
           <td colspan="3" style=" text-align: start;text-transform: uppercase;"></td>
           <td colspan="2" style="text-align: start; ">Grand Total:-</td>
           <td style=" text-align: center;">₹  </td>
        </tr> -->
        <tr>
            <td colspan="3" style=" text-align: start;text-transform: uppercase;"></td>
            <td colspan="2" style="text-align: start; ">Discount:-</td>
            <td style=" text-align: center;">{{$discountAmount}}</td>
         </tr>
        
        <tr>
            <td colspan="3" style=" text-align: start;text-transform: uppercase;"></td>
            <td  colspan="2"  style="text-align: start; ">Paid Amount:-</td>
            <td style=" text-align: center;">{{$total_paid}}</td>
         </tr>
         <tr>
            <td colspan="3" style=" text-align: start;text-transform: uppercase;"></td>
            <td colspan="2" style="text-align: start; ">Balance:-</td>
            <td style=" text-align: center;">{{$totalAmount}}</td>
         </tr> 
    
    </table>
    <p><br></p>
    <table style="width: 50%;">
        <tr>
            <td colspan="4" style="width: 100%;text-align: center;text-transform: uppercase;font-weight: bold;border:none">Payment details</td>
        </tr>
        <tr style="background-color: #255a9b; color: #fff;">
            <th style="width: 50%;">Date</th>
            <th style="width: 20%;">Receipt Number</th>
            <th style="width: 20%;">Mode of
                Payment</th>
            <th style="width: 10%;">Paid Amount
                INR</th>
        </tr>
        @foreach ($payments as $payment) 
        <tr>
            <td style="text-align: center;">{{ date('d-m-Y', strtotime($payment['payment_date'])) }}</td>
            <td style="text-align: center;">{{ $payment['receipt_no'] }}</td>
            <td style="text-align: center;">{{ $payment['payment_type'] }}</td>
            <td style="text-align: center;">{{ $payment['paid_amount'] }}</td>
        </tr>
        @endforeach
       
        
        </table> 
        <table style="width: 100%;">
            <td style="padding-top: 20px;text-align: start;font-weight: 900; border: none;">Notes:-</td>
            </table>
    <table style="width: 100%;">
    <td style="padding-top: 70px;text-align: end;font-weight: 900; border: none;">Authorized Signature</td>
    </table>
    
    <br/>
    <br/>
    
     <table style="border-top: 2px solid #000; position: absolute; bottom: 30; left: 0;">
    <tr>
        <td style="font-size: 14px; border: 1px solid #fff;">
            Balance Assessment & Rehabilitation. • Speech Therapy. • Swallowing Therapy. (Dysphagia Therapy) • Voice
            Therapy. • Clinical Psychologist • Speech Therapy • ABA Therapy. (Autism) • Physiotherapy. (Adult &
            Child) •
            Dietary & Nutritional Consultant • Occupational Therapy. (SI Therapy)
        </td>
    </tr>
</table>
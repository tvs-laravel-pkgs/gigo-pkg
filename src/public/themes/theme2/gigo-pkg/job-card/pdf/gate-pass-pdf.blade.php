<!DOCTYPE html>
<html dir="ltr" lang="en-US" style="width: 793px; margin: 0 auto;">
    <head>
        <title>Gate Pass</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {width: 100%;color: #212121;padding: 0px;line-height: normal;margin: 0;}             
            table {font-family: arial, sans-serif;border-collapse: collapse;width: 100%;margin-top: 0px;margin-bottom: 0;}
            table:last-child {margin-bottom: 0;} 
            th {font-size: 12px;font-weight: bold;line-height: normal;padding: 5px;}
            td {font-size: 12px;line-height: normal;padding: 5px;}
            .table-outter > tbody > tr > td {padding: 20px;border: 1px solid #000;}
            .table-header {margin-bottom: 10px;}
            .table-header > tbody > tr > td {padding: 0;padding-bottom: 15px;border-bottom: 1px solid #28508d;vertical-align: middle;}
            .table-address {margin-bottom: 15px;}
            .table-address > tbody > tr > td {width: 50%;padding: 0 0 15px 0;vertical-align: top;border-bottom: 1px solid #cfd8dc;}
            .table-address > tbody > tr > td:first-child {padding-right: 190px;}
            .table-address > tbody > tr > td:last-child {padding-left: 210px;}
            .table-lbl-val {margin-bottom: 20px;}
            .table-lbl-val > tbody > tr > td {width: 50%;padding: 0;vertical-align: top;}
            .table-lbl-val > tbody > tr > td:nth-child(odd) {padding-right: 10px;}
            .table-lbl-val > tbody > tr > td:nth-child(even) {padding-left: 10px;}
            .table-lbl-val-seprt {width: auto;}
            .table-lbl-val-seprt > tbody > tr > td {font-size: 10px;font-weight: bold;padding: 0;padding-bottom: 8px;vertical-align: top;}
            .table-lbl-val-seprt > tbody > tr > td.table-lbl {color: #212121;}
            .table-lbl-val-seprt > tbody > tr > td.table-seprt {color: #212121;padding-left: 30px;padding-right: 10px;}
            .table-lbl-val-seprt > tbody > tr > td.table-val {color: #424242;}
            .table-two-col {margin-bottom: 20px;}
            .table-two-col > tbody > tr > td {width: 50%;padding: 0;vertical-align: top;}
            .table-two-col > tbody > tr > td:nth-child(odd) {padding-right: 7.5px;}
            .table-two-col > tbody > tr > td:nth-child(even) {padding-left: 7.5px;}
            .table-details > thead > tr > th {font-size: 8px;font-weight: bold;color: #ffffff;background-color: #28508d;padding: 7px 8px;border: 1px solid #28508d;}
            .table-details > tbody > tr > td {font-size: 8px;font-weight: bold;padding: 6px 8px;border: 1px solid #28508d;}
            .table-ltr-cnt {margin-bottom: 10px;}
            .table-ltr-cnt > tbody > tr > td {padding: 0;}
            .table-ltr-cnt > tbody > tr > td p {font-size: 10px;line-height: 1.5;margin-bottom: 8px;min-height: 1px;}
            .table-bill {margin-bottom: 15px;}
            .table-bill > thead > tr > th {font-size: 8px;font-weight: bold;padding: 7px 6px;text-align: center;text-transform: capitalize;background-color: rgba(40, 80, 141, 0.16);border: 1px solid #28508d;}
            .table-bill > tbody > tr > td {font-size: 8px;font-weight: bold;padding: 6px 6px;border: 1px solid #28508d;}
            .table-signature > tbody > tr > td {width: 33.33333%;vertical-align: bottom;padding: 0;}

            p {margin: 0;}
            .mt-20 {margin-top: 20px;}
            .mb-25 {margin-bottom: 25px!important;}
            .text-center {text-align: center;}
            .text-left {text-align: left;}
            .text-right {text-align: right;}
            .opacity-0 {opacity: 0;}
            .vertical-top {vertical-align: top;}
            .color-blue {color: #28508d;}
            .block {display: block;}
            .inline-block {display: inline-block;}
            .table-header-title {font-size: 14px;color: #212121;margin: 0;margin-bottom: 5px;line-height: normal;}
            .table-header-subtitle {font-size: 12px;font-weight: bold;color: #1a3885;}
            .header-logo-img {font-size: 14px;font-weight: bold;color: #1a3885;text-transform: capitalize;}
            .header-logo-img > span {display: block;}
            .header-logo-img img {width: 30px;display: inline-block;vertical-align: middle;}
            .address-label {font-size: 10px;font-weight: bold;margin-bottom: 4px;}
            .address-value {font-size: 10px;color: #424242;line-height: 1.6;}
            .sprtr-brdr {min-height: 1px;width: 140px;margin: 60px 0 15px;border-bottom: 1px solid #28508d;}
            .sprtr-brdr span {visibility: hidden;}
            .text-center .sprtr-brdr {margin-left: auto;margin-right: auto;}
            .text-right .sprtr-brdr {margin-left: auto;}
            .signature-value {font-size: 10px;font-weight: bold;font-style: italic;}
            .signature-value.fnt-nrml {font-style: normal;}
            .text-right .signature-value.wid-140 {margin-right: 0;margin-left: auto;}
            .signature-value.wid-140 {width: 140px;}
        </style>
    </head>
    <body>
        <!-- ORDER FORM -->
        <table class="table-outter">
            <tbody>
                <tr>  
                    <td>
                        <table class="table-header">
                            <tbody>
                                <tr>
                                    <td class="text-left">
                                        <h1 class="table-header-title">T V Sundram Iyengar & Sons Private Ltd</h1>
                                        <p class="table-header-subtitle">GSTIN No : 33AABCT0159K1ZG</p>
                                    </td>
                                    <td class="text-right header-logo-img">
                                        <span>
                                            Gate Pass
                                            <img class="img-responive header-logo-img" src="{{ URL::asset('public/theme/img/tvs.svg') }}" alt="Logo" />
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Header -->
                        <table class="table-address">
                            <tbody>
                                <tr>
                                    <td>
                                        <p class="address-label">Dealer Address:</p>
                                        <p class="address-value">S.No: 88/1, Salem to Bangalore Highway, Karuppur, Opp.Govt College of Eng, Salem 636011</p>
                                    </td>
                                    <td>
                                        <p class="address-label">Registered Office:</p>
                                        <p class="address-value">TVS Building, 7-B West Veli Street, Madurai 625008, Tamil Nadu, India</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Address -->
                        <table class="table-lbl-val">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="table-lbl-val-seprt">
                                            <tbody>
                                                <tr>
                                                    <td class="table-lbl">Gate Pass No</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                                <tr>
                                                    <td class="table-lbl">Job Card No</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                                <tr>
                                                    <td class="table-lbl">Dealer Specific No</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                                <tr>
                                                    <td class="table-lbl">Customer Name</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                                <tr>
                                                    <td class="table-lbl">Customer Address</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td>
                                        <table class="table-lbl-val-seprt">
                                            <tbody>
                                                <tr>
                                                    <td class="table-lbl">Date</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Label Value -->
                        <table class="table-two-col">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="table-details">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="2">VEHICLE DETAILS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Vehicle Model</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Registration No</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Chassis No</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Aggregate No</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Engine No</td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table><!-- Table Details-->
                                    </td>
                                    <td>
                                        <table class="table-details">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" colspan="2">JOB CARD DETAILS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Creation Date</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Repair Type</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Service Type</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Km / Hr Reading</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>Cum. Km / Hr Reading</td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </table><!-- Table Details-->
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Two Column -->
                        <table class="table-bill">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Checklist Items</th>
                                    <th>Inward</th>
                                    <th>Inward Remark</th>
                                    <th>Outward</th>
                                    <th>Outward Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table><!-- Table Bill -->
                        <table class="table-ltr-cnt">
                            <tbody>
                                <tr>
                                    <td>
                                        <p><b>Terms & Conditions:</b></p>
                                        <p>I have this day taken delivery of above Vehicle/Spares after repairs in good order and to my satisfaction. I hereby agree to the various jobs listed in the Jobcard No. <b class="color-blue">FRE24781819000016</b> and further accept to pay the amount in the jobcard invoice being charges for repair and parts replaced to the above vehicle/spares along with interest at 18% per annum and other incidentals as per your trade conditions.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Letter Content-->
                        <table class="table-lbl-val">
                            <tbody>
                                <tr>
                                    <td>
                                        <table class="table-lbl-val-seprt">
                                            <tbody>
                                                <tr>
                                                    <td class="table-lbl">Supervised by</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                                <tr>
                                                    <td class="table-lbl">Service Advisor</td>
                                                    <td class="table-seprt">:</td>
                                                    <td class="table-val"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- Table Label Value -->
                        <table class="table-signature">
                            <tbody>
                                <tr>
                                    <td>
                                        <p class="sprtr-brdr">
                                            <span>Border</span>
                                        </p>
                                        <p class="signature-value">Security Incharge</p>
                                    </td>
                                    <td class="text-center">
                                        <p class="sprtr-brdr">
                                            <span>Border</span>
                                        </p>
                                        <p class="signature-value">Customer Signature</p>
                                    </td>
                                    <td class="text-right">
                                        <p class="signature-value fnt-nrml text-left wid-140">For :</p>
                                        <p class="sprtr-brdr">
                                            <span>Border</span>
                                        </p>
                                        <p class="signature-value">Authorized Signatory & Date</p>
                                    </td>
                            </tbody>
                        </table><!-- Table Signature -->
                    </td>
                </tr>
            </tbody>
        </table><!-- Table Outter -->
    </body>
</html>
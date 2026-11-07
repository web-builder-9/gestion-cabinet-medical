{{-- Styles de la feuille d'ordonnance (CSS simple, compris par dompdf et par les navigateurs). --}}
<style>
    .rx { font-family: "DejaVu Sans", Inter, Arial, sans-serif; font-size: 11px; line-height: 1.45; color: #0f2027; }
    .rx p { margin: 0; }
    .rx table { width: 100%; border-collapse: collapse; }
    .rx td { vertical-align: top; padding: 0; }
    .rx-head { border-bottom: 2px solid #0f766e; }
    .rx-head td { padding-bottom: 14px; }
    .rx-doctor { font-size: 18px; font-weight: bold; color: #0f766e; }
    .rx-specialty { margin-bottom: 6px !important; }
    .rx-muted { color: #56676c; }
    .rx-right { text-align: right; }
    .rx-cross { font-size: 26px; color: #0f766e; line-height: 1; margin-bottom: 10px !important; }
    .rx-title { margin: 18px 0 12px; text-align: center; font-size: 15px; letter-spacing: 6px; text-transform: uppercase; }
    .rx-patient { margin-bottom: 14px !important; }
    .rx-items td { padding: 10px 0; border-bottom: 1px dashed #c9d4d7; }
    .rx-number { width: 30px; }
    .rx-number span { display: inline-block; width: 20px; height: 20px; line-height: 20px; border-radius: 10px; background: #e3f2ef; color: #0f766e; text-align: center; font-weight: bold; font-size: 10px; }
    .rx-medication { font-size: 12.5px; font-weight: bold; }
    .rx-advice { margin-top: 14px !important; }
    .rx-signature { margin-top: 40px; }
    .rx-signature td { width: 55%; }
    .rx-signature-box { width: 45% !important; height: 70px; border: 1px dashed #c9d4d7; text-align: center; vertical-align: bottom !important; padding-bottom: 6px !important; color: #56676c; }
    .rx-footer { margin-top: 18px !important; padding-top: 8px; border-top: 1px solid #e1e8ea; text-align: center; font-size: 9px; color: #56676c; }
</style>

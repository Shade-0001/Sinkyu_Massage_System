# ページヘッダー実装マッピング

## 対象ビューファイルとルート名・ページタイトル

| ビューファイル | ルート名 | ページタイトル | 階層 |
|---|---|---|---|
| records/records_index.blade.php | records.index | 実績データ | ホーム > 実績データ |
| clinic-users/clinic-users_index.blade.php | clinic-users.index | 利用者情報 | ホーム > マスター登録 > 利用者情報 |
| index.blade.php | index | ホーム | ホーム |
| master/document-association/document-association_index.blade.php | master.document-association.index | 標準文書の確認および関連付け | ホーム > マスター登録 > 標準文書の確認および関連付け |
| master/documents/documents_index.blade.php | master.documents.index | 文面編集 | ホーム > マスター登録 > 文面編集 |
| submaster/submaster_index.blade.php | submaster.index | サブマスター登録 | ホーム > マスター登録 > サブマスター登録 |
| master/self-fees/self-fees_index.blade.php | master.self-fees.index | 自費施術料金編集 | ホーム > マスター登録 > 自費施術料金編集 |
| submaster/self-paid-fees.blade.php | - | サブマスター | (独自階層) |
| master/documents/documents_registration.blade.php | master.documents.create/edit/duplicate | 文面編集‐登録 | ホーム > マスター登録 > 文面編集 > 登録 |
| submaster/illnesses-massage.blade.php | submaster.illnesses-massage | 疾病（あんま・マッサージ） | ホーム > マスター登録 > サブマスター登録 > 疾病（あんま・マッサージ） |
| submaster/service-providers.blade.php | submaster.service-providers | サービス提供者 | ホーム > マスター登録 > サブマスター登録 > サービス提供者 |
| submaster/medical-institutions.blade.php | submaster.medical-institutions | 医療機関 | ホーム > マスター登録 > サブマスター登録 > 医療機関 |
| submaster/conditions.blade.php | submaster.conditions | 状態 | ホーム > マスター登録 > サブマスター登録 > 状態 |
| master/master_index.blade.php | master.index | マスター登録 | ホーム > マスター登録 |
| master/treatment-fees/treatment-fees_index.blade.php | master.treatment-fees.index | 施術料金編集 | ホーム > マスター登録 > 施術料金編集 |
| clinic-info/clinic-info_index.blade.php | clinic-info.index | 自社情報 | ホーム > マスター登録 > 自社情報 |
| master/treatment-fees/treatment-fees_registration.blade.php | master.treatment-fees.create/edit | 施術料金編集‐登録 | ホーム > マスター登録 > 施術料金編集 > 登録 |
| caremanagers/caremanagers_registration.blade.php | caremanagers.create/edit | ケアマネ情報‐登録 | ホーム > マスター登録 > ケアマネ情報 > 登録 |
| therapists/therapists_registration.blade.php | therapists.create/edit | 施術者情報‐登録 | ホーム > マスター登録 > 施術者情報 > 登録 |
| clinic-users/consents-acupuncture/consents-acupuncture_registration.blade.php | clinic-users.consents-acupuncture.registration/edit/duplicate | 同意医師履歴（鍼灸）‐登録 | ホーム > マスター登録 > 利用者情報 > 同意医師履歴（鍼灸） > 登録 |
| clinic-users/consents-massage/consents-massage_registration.blade.php | clinic-users.consents-massage.create/edit/duplicate | 同意医師履歴（あんま・マッサージ）‐登録 | ホーム > マスター登録 > 利用者情報 > 同意医師履歴（あんま・マッサージ） > 登録 |
| clinic-users/insurances/insurances_registration.blade.php | clinic-users.insurances.create/edit/duplicate | 保険情報‐登録 | ホーム > マスター登録 > 利用者情報 > 保険情報 > 登録 |
| clinic-users/clinic-users_registration.blade.php | clinic-users.create/edit | 利用者情報‐登録 | ホーム > マスター登録 > 利用者情報 > 登録 |
| profile/edit.blade.php | profile.edit | プロフィール | - |
| caremanagers/caremanagers_index.blade.php | caremanagers.index | ケアマネ情報 | ホーム > マスター登録 > ケアマネ情報 |
| therapists/therapists_index.blade.php | therapists.index | 施術者情報 | ホーム > マスター登録 > 施術者情報 |
| clinic-users/plans/plans_index.blade.php | clinic-users.plans.index | 計画情報 | ホーム > マスター登録 > 利用者情報 > 計画情報 |
| clinic-users/plans/plans_registration.blade.php | clinic-users.plans.create/edit/duplicate | 計画情報‐登録 | ホーム > マスター登録 > 利用者情報 > 計画情報 > 登録 |
| clinic-users/consents-massage/consents-massage_index.blade.php | clinic-users.consents-massage.index | 同意医師履歴（あんま・マッサージ） | ホーム > マスター登録 > 利用者情報 > 同意医師履歴（あんま・マッサージ） |
| clinic-users/consents-acupuncture/consents-acupuncture_index.blade.php | clinic-users.consents-acupuncture.index | 同意医師履歴（鍼灸） | ホーム > マスター登録 > 利用者情報 > 同意医師履歴（鍼灸） |
| clinic-users/insurances/insurances_index.blade.php | clinic-users.insurances.index | 保険情報 | ホーム > マスター登録 > 利用者情報 > 保険情報 |
| registration-done.blade.php | - | 登録完了 | (確認画面から遷移) |

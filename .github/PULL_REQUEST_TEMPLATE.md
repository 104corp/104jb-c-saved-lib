## Check List

- 需求單\[JIRA單號\] (Code Owner 填寫)
    - [連結]：

- Pull Request (Code Owner 檢查)
    - [ ] 確實填寫需求單、上線單號。
    - [ ] 確認添加對應的環境標籤（staging, prod）。
    - [ ] 通過一名以上團隊內的工程師或上一級主管審核。
    - [ ] 版號或 PR 連結是否有更新到 ITPM 程式清單中。
    - [ ] 跨瀏覽器（IE11）檢查、測試。
    - [ ] 當有需要儲存個資時，是否依照規範加密。
    - [ ] 當需要有使用個資時，是否依照規範儲存 log。
    - [ ] 當有使用到的 SOAP API 需一同上線，程式要在 API 上線完成後的 5 分鐘再進行上線。
    - [ ] 當有使用到的前端共用元件是跨產品單位使用，要進行通知。
    - [ ] 上線併版時，如遇到要解衝突，需請相關開發人員一同確認。

- Code Review (Reviewer 檢查)
    - [ ] PR、Commit 遵循。 [版本控制規範 > Commit Title 格式規範](https://github.com/104corp/guideline/tree/master/vcs)
    - [ ] 程式碼的行為是否符合開發者（需求）的預期。
    - [ ] 程式碼對於呼叫外部服務所做的例外處理是否完整。
    - [ ] 程式碼是否可以簡化。
    - [ ] 程式碼是否清晰的命名變數。
    - [ ] 程式碼在非緊急狀況、時程壓力下，是否有自動化測試。
    - [ ] 相關文件是否同時更新。

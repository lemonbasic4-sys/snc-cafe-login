```powershell
cd C:\xampp\htdocs\snc-cafe-login

C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source database/schema.sql"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source database/user_accounts.sql"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root -e "source database/seed_menu.sql"
```

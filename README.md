Simple module to manage reservation of court using Joomla

dump database:
  mysqldump --no-tablespaces -u <user>  -h <server> -p <database name> > backup.sql

restore database:
  mysql -u <user> -p <database name> < backup.sql

helper
- getAjax
   cmds 'reserve'       date, hour, resType, player1, player2
        'free'          date, hour
        'reserveCancel'
        'prevCal'
        'nextCal'
        'currCal'                -> buildCalendar()
        'calHeader'              -> buildCalHeader()
        'getUsersName'
        'selPlayer'     date, hour -> showSelPlayer(date, hour)
        'getStrings'

- getWeekReservation(date)
   - resDelete(null, null)
   - get data from db

- buildCalendar(cmd, width)

- showSelPlayer(date, hour)
   - resInsert(user->id, NULL, date, RES_TYPE_OPENED)
   - show message
   - show player1 and player2 fields

- resInsert(user1, user2, date, type)
   - add data in db

- checkUserBusy(user)

- resUpdate(user1, user2, date, type)
   - checkUserBusy(user1)
   - checkUserBusy(user2)
   - update data in db

- resDelete(userId, date)
   - delete data in db
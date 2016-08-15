import java.sql.*;
import java.util.MissingResourceException;
import java.util.ResourceBundle;

public class voyagerQueryToJson {

  public static String getValue(String key) {
    try {
      return ResourceBundle.getBundle("voyager").getString(key);
    } catch (MissingResourceException e) {
      return "MISSING RESOURCE KEY: " + key; 
    }
  }

  public static void main(String[] args) {
    StringBuffer json = new StringBuffer();
    json.append("{");
    
    try {
      String driverClass = voyagerQueryToJson.getValue("driverClass");
      String url = voyagerQueryToJson.getValue("url");
      String username  = voyagerQueryToJson.getValue("username");
      String password = voyagerQueryToJson.getValue("password");

      Class.forName(driverClass);
      Connection conn = DriverManager.getConnection(url,username,password);
      conn.setAutoCommit(false);
      Statement stmt = conn.createStatement();
      ResultSet rset = stmt.executeQuery(args[0]);
      ResultSetMetaData rsMetaData = rset.getMetaData();
      int numberOfColumns = rsMetaData.getColumnCount();

      json.append("\"results\":[");
      int record_count = 0;
      while (rset.next()) {
        if (record_count > 0) {
          json.append(",");
        }
        json.append("{");
         for (int i = 1; i < numberOfColumns + 1; i++) {
           if (i > 1) {
             json.append(",");
           }
           json.append("\"" + rsMetaData.getColumnName(i) + "\":");
           if (rset.getString(i) != null && ! rset.getString(i).equals("")) {
            json.append("\"" + rset.getString(i).replaceAll("\"", "\\\\\"") + "\"");
           } else {
             json.append("\"\"");
           }
         }
        json.append("}");
        record_count++;
      }
      json.append("]");

      rset.close();
      stmt.close();
      conn.close();

    } catch (ClassNotFoundException e) {
      System.out.println(e.toString());
    } catch (SQLException e) {
      System.out.println(e.toString());
    }

    json.append("}");
    System.out.println(json.toString());
  }

}

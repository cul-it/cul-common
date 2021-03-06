import javax.naming.*;
import javax.naming.directory.*;
import java.util.Hashtable;

public class SimpleQuery {

    public static void main(String[] args) {

        if (args.length != 2) {
          System.out.println("Syntax: SimpleQuery query attribute");
          return;
        }

        String query = args[0];
        String attribute = args[1];
        StringBuffer output = new StringBuffer();

        try {
            String url = "ldap://directory.cornell.edu/o=Cornell%20University,c=US";
            Hashtable env = new Hashtable();
            env.put(Context.INITIAL_CONTEXT_FACTORY, "com.sun.jndi.ldap.LdapCtxFactory");
            env.put(Context.PROVIDER_URL, url);
            DirContext context = new InitialDirContext(env);

            SearchControls ctrl = new SearchControls();
            ctrl.setSearchScope(SearchControls.SUBTREE_SCOPE);
            NamingEnumeration enumeration = context.search("", query, ctrl);
            while (enumeration.hasMore()) {
                SearchResult result = (SearchResult) enumeration.next();
                Attributes attribs = result.getAttributes();
                NamingEnumeration values = ((BasicAttribute) attribs.get(attribute)).getAll();
                while (values.hasMore()) {
                  if (output.length() > 0) {
                    output.append("|");
                  }
                  output.append(values.next().toString());
                }
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
        System.out.print(output.toString());
    }

    public SimpleQuery() {}
}


"""Local dev server — serves static files and redirects /page → /page.html"""
import http.server
import os

class Handler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        # Strip query string for path check
        path = self.path.split('?')[0].split('#')[0]
        # If path has no extension and isn't a directory, try adding .html
        if '.' not in os.path.basename(path) and not path.endswith('/'):
            html_path = path.rstrip('/') + '.html'
            full = self.translate_path(html_path)
            if os.path.isfile(full):
                self.send_response(301)
                self.send_header('Location', html_path)
                self.end_headers()
                return
        super().do_GET()

    def log_message(self, fmt, *args):
        print(fmt % args)

if __name__ == '__main__':
    os.chdir(os.path.dirname(os.path.abspath(__file__)))
    http.server.test(HandlerClass=Handler, port=8080, bind='localhost')

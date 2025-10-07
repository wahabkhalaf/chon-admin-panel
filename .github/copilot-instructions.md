# Custom Instructions for Laravel Filament 3

## General Guidelines
- When providing instructions and code samples for Laravel Filament 3, always use the latest stable version.
- Ensure that all code samples are formatted with proper indentation and syntax highlighting.
- Ensure that all provided examples are tested and functional within a Laravel Filament 3 application.

## Testing Guidelines with Pest PHP

### Environment Setup
- Using Pest PHP version ^3.7
- Tests should be placed in the `tests` directory
- Follow PSR-4 autoloading standards

### Test Structure
1. **Editor Setup**
   - Use proper IDE plugins for Pest
   - Configure proper test discovery

2. **Writing Tests**
   - Use descriptive test names
   - Follow Arrangement-Action-Assertion pattern
   - Keep tests focused and atomic

3. **Expectations**
   - Use Pest's expressive expectation syntax
   - Chain expectations when logical
   - Use custom expectations for Filament components

4. **Hooks**
   - Utilize `beforeEach()` and `afterEach()`
   - Set up proper database transactions
   - Clean up resources after tests

5. **Datasets**
   - Use `with()` for data providers
   - Implement meaningful test cases
   - Cover edge cases

6. **Exception Testing**
   - Test expected exceptions
   - Verify error messages
   - Check exception chains

7. **Test Filtering**
   - Use proper groups and annotations
   - Implement test segregation
   - Tag tests appropriately

8. **Skip Conditions**
   - Document skip reasons
   - Use environment-based skipping
   - Handle version-specific tests

9. **Performance**
   - Optimize database operations
   - Use proper mocking strategies
   - Implement parallel testing when possible

10. **CI Integration**
    - Configure GitHub Actions
    - Set up test reporting
    - Implement coverage tracking

### Best Practices
- Write tests before implementation (TDD when possible)
- Keep tests maintainable and readable
- Follow single responsibility principle
- Document complex test scenarios



use docker-compose for consistent environments
- Regularly review and refactor tests
- Stay updated with Pest and Filament releases
- Engage with the community for best practices and support
- Use version control for test files
- Ensure tests are part of the CI/CD pipeline
- Use meaningful commit messages for test changes
- Regularly run tests locally before pushing changes
- Monitor test coverage and aim for high coverage
- Use static analysis tools to ensure code quality
- Avoid hardcoding values; use configuration files or environment variables
- Ensure tests are idempotent and can run in any order
- Use factories and seeders for test data
- Mock external services and APIs
<% require css(redmine/css/styles.css) %>
<div id="IssueHolder">
	<h1>Issues</h1>
	<% if Issues %>
	<table class="issues" width="100%">
		<thead>
			<tr>
				<th>ID</th>
				<th>Subject</th>
				<th>Status</th>
				<th>Priority</th>
				<th>Reported</th>
				<th>Last Updated</th>
				<th>Estimated Hours</th>
				<th>Progress</th>
			</tr>
		</thead>
	<% control Issues %>
		<tr>
			<td>$id</td>
			<td>$subject</td>
			<td>$status</td>
			<td class="hotsort$prioritySort">$priority</td>
			<td>$created_on</td>
			<td>$updated_on</td>
			<td>$estimated_hours</td>
			<td>
				<table class="progress">
					<tr>
						<% if done_ratio %><td class="closed" style="width:{$done_ratio}%"></td><% end_if %>
						<% if done_ratio = 100 %><% else %><td></td><% end_if %>
					</tr>
				</table>
			{$done_ratio}%</td>
		</tr>
		<tr>
			<td colspan="9">
				<div class="description">
					$description
				</div>
			</td>
		</tr>
	<% end_control %>
	</table>
	<% end_if %>
	
</div>

